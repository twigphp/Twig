<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests;

use PHPUnit\Framework\TestCase;
use Twig\BlockChain;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\ProfilerExtension;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Profiler\Profile;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

class BlockChainTest extends TestCase
{
    public function testComposesCompleteTemplateLineagesInPrecedenceOrder(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme1' => '{% extends "parent1" %}{% block first %}theme1{% endblock %}',
            'parent1' => '{% block shared %}parent1{% endblock %}{% block parent1 %}parent1{% endblock %}',
            'theme2' => '{% extends "parent2" %}{% block shared %}theme2{% endblock %}{% block second %}theme2{% endblock %}',
            'parent2' => '{% block parent2 %}parent2{% endblock %}',
        ]), ['autoescape' => false]);

        $chain = new BlockChain($twig, ['theme1', $twig->load('theme2')]);

        $this->assertSame(['first', 'shared', 'parent1', 'second', 'parent2'], $chain->getBlockNames());
        $this->assertTrue($chain->hasBlock('shared'));
        $this->assertFalse($chain->hasBlock('missing'));
        $this->assertSame('parent1', $chain->renderBlock('shared'));
        $this->assertSame('theme2', $chain->renderBlock('second'));
    }

    public function testNestedBlocksUseTheComposedSetAndParentUsesTheRenderContextLineage(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends parent %}{% block field %}theme/{{ parent() }}/{{ block("suffix") }}{% endblock %}',
            'parent1' => '{% block field %}parent1{% endblock %}',
            'parent2' => '{% block field %}parent2{% endblock %}',
            'suffix' => '{% block suffix %}suffix{% endblock %}',
        ]), ['autoescape' => false]);

        $chain = new BlockChain($twig, ['theme', 'suffix'], ['parent' => 'parent1']);

        $this->assertSame('theme/parent1/suffix', $chain->renderBlock('field'));
        $this->assertSame('theme/parent2/suffix', $chain->renderBlock('field', ['parent' => 'parent2']));
    }

    public function testExplicitTemplateBlockCallsResolveOutsideTheChainNamespace(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% block field %}{{ block("suffix", "explicit") }}{% endblock %}',
            'chain' => '{% block suffix %}chain{% endblock %}',
            'explicit' => '{% block suffix %}explicit{% endblock %}',
        ]), ['autoescape' => false]);

        $chain = new BlockChain($twig, ['theme', 'chain']);

        $this->assertSame('explicit', $chain->renderBlock('field'));
    }

    public function testTheConstructorContextIsADefaultTheRenderContextCanOverride(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends parent %}{% block field %}{{ parent() }}{% endblock %}',
            'parent1' => '{% block field %}one{% endblock %}',
            'parent2' => '{% block field %}two{% endblock %}',
        ]), ['autoescape' => false]);
        $chain = new BlockChain($twig, ['theme'], ['parent' => 'parent1']);

        $this->assertSame('one', $chain->renderBlock('field'));
        $this->assertSame('two', $chain->renderBlock('field', ['parent' => 'parent2']));
        $this->assertSame('one', $chain->renderBlock('field'));
    }

    public function testIntrospectionAndRenderingAgreeOnTheSameContext(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends parent %}{% block field %}{{ parent() }}{% endblock %}',
            'parent1' => '{% block field %}one{% endblock %}{% block only1 %}{% endblock %}',
            'parent2' => '{% block field %}two{% endblock %}{% block only2 %}{% endblock %}',
        ]), ['autoescape' => false]);
        $chain = new BlockChain($twig, ['theme'], ['parent' => 'parent1']);

        $this->assertSame(['field', 'only1'], $chain->getBlockNames());
        $this->assertTrue($chain->hasBlock('only1'));
        $this->assertFalse($chain->hasBlock('only2'));

        $context = ['parent' => 'parent2'];
        $this->assertSame(['field', 'only2'], $chain->getBlockNames($context));
        $this->assertFalse($chain->hasBlock('only1', $context));
        $this->assertTrue($chain->hasBlock('only2', $context));
        $this->assertSame('two', $chain->renderBlock('field', $context));
    }

    public function testRenderingThroughTheChainDoesNotChangeTheLoadedTemplate(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends parent %}{% block field %}{{ parent() }}{% endblock %}',
            'parent1' => '{% block field %}one{% endblock %}',
            'parent2' => '{% block field %}two{% endblock %}',
        ]), ['autoescape' => false]);
        $template = $twig->load('theme');
        $chain = new BlockChain($twig, [$template], ['parent' => 'parent1']);

        $this->assertSame('one', $chain->renderBlock('field'));
        $this->assertSame('two', $template->renderBlock('field', ['parent' => 'parent2']));
        $this->assertSame('one', $chain->renderBlock('field'));
    }

    public function testStructuralContextIncludesEnvironmentGlobals(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends layout %}{% block field %}{{ parent() }}{% endblock %}',
            'parent' => '{% block field %}parent{% endblock %}',
        ]), ['autoescape' => false]);
        $twig->addGlobal('layout', 'parent');

        $chain = new BlockChain($twig, ['theme']);

        $this->assertSame('parent', $chain->renderBlock('field'));
    }

    public function testTraitAliasesKeepTheirLocalParentLineage(): void
    {
        $twig = new Environment(new ArrayLoader([
            'base_trait' => '{% block field %}base{% endblock %}',
            'trait' => '{% use "base_trait" %}{% block field %}trait/{{ parent() }}{% endblock %}',
            'theme' => '{% use "trait" with field as aliased %}',
        ]), ['autoescape' => false]);

        $chain = new BlockChain($twig, ['theme']);

        $this->assertSame(['aliased'], $chain->getBlockNames());
        $this->assertSame('trait/base', $chain->renderBlock('aliased'));
    }

    public function testRenderingDisplayingAndStreamingAddGlobals(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% block field %}{{ local }}:{{ global|default("none") }}{% endblock %}',
        ]), ['autoescape' => false]);
        $twig->addGlobal('global', 'GLOBAL');
        $chain = new BlockChain($twig, ['theme']);

        $this->assertSame('LOCAL:GLOBAL', $chain->renderBlock('field', ['local' => 'LOCAL']));

        ob_start();
        $chain->displayBlock('field', ['local' => 'LOCAL']);
        $this->assertSame('LOCAL:GLOBAL', ob_get_clean());

        $streamed = '';
        foreach ($chain->streamBlock('field', ['local' => 'LOCAL']) as $data) {
            $streamed .= $data;
        }
        $this->assertSame('LOCAL:GLOBAL', $streamed);
    }

    public function testRejectsInvalidBlockDefinitions(): void
    {
        $twig = new Environment(new ArrayLoader());
        $template = new class($twig) extends BlockChainTestTemplate {
            public function __construct(Environment $env)
            {
                parent::__construct($env);
                $this->blocks = ['field' => [new \stdClass(), 'block_field']];
            }
        };
        $chain = new BlockChain($twig, [new TemplateWrapper($twig, $template)]);
        $this->assertSame(['field'], $chain->getBlockNames());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A block must be a method on a \Twig\Template instance.');

        $chain->renderBlock('field');
    }

    public function testRejectsTemplatesThatAreNotStringsOrWrappers(): void
    {
        $twig = new Environment(new ArrayLoader(['theme' => '']));

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Block chain templates must be strings or "Twig\TemplateWrapper" instances, "stdClass" given.');

        new BlockChain($twig, [new \stdClass()]);
    }

    public function testRejectsWrappersFromAnotherEnvironment(): void
    {
        $twig = new Environment(new ArrayLoader(['theme' => '']));
        $other = new Environment(new ArrayLoader(['theme' => '']));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A block chain cannot contain templates from different Twig environments.');

        new BlockChain($twig, [$other->load('theme')]);
    }

    public function testRejectsWrappersThatHideATemplateFromAnotherEnvironment(): void
    {
        $twig = new Environment(new ArrayLoader(['theme' => '']));
        $other = new Environment(new ArrayLoader(['theme' => '']));
        $wrapper = new TemplateWrapper($twig, $other->load('theme')->unwrap());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A block chain cannot contain templates from different Twig environments.');

        new BlockChain($twig, [$wrapper]);
    }

    public function testRejectsDynamicParentsFromAnotherEnvironment(): void
    {
        $twig = new Environment(new ArrayLoader(['theme' => '{% extends parent %}']));
        $other = new Environment(new ArrayLoader(['parent' => '']));
        $chain = new BlockChain($twig, ['theme'], ['parent' => $other->load('parent')]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A block chain cannot contain templates from different Twig environments.');

        $chain->getBlockNames();
    }

    public function testDynamicParentSecurityIsCheckedWhenTheLineageIsResolved(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends parent|upper %}',
            'PARENT' => '',
        ]));
        $twig->addExtension(new SandboxExtension(new SecurityPolicy(['extends']), true));
        $chain = new BlockChain($twig, ['theme'], ['parent' => 'parent']);

        $this->expectException(SecurityNotAllowedFilterError::class);

        $chain->getBlockNames();
    }

    public function testDefiningTemplateSecurityIsCheckedDuringRendering(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% block field %}{{ value|upper }}{% endblock %}',
        ]));
        $twig->addExtension(new SandboxExtension(new SecurityPolicy(['block']), true));
        $chain = new BlockChain($twig, ['theme']);

        $this->expectException(SecurityNotAllowedFilterError::class);

        $chain->renderBlock('field', ['value' => 'value']);
    }

    public function testSandboxPolicyChangesAreObservedAfterConstruction(): void
    {
        $twig = new Environment(new ArrayLoader([
            'policy_theme' => '{% extends "parent" %}{% block field %}{{ value|upper }}{% endblock %}',
            'parent' => '',
        ]), ['autoescape' => false]);
        $sandbox = new SandboxExtension(new SecurityPolicy(['extends', 'block'], ['upper']), true);
        $twig->addExtension($sandbox);
        $chain = new BlockChain($twig, ['policy_theme']);

        $this->assertSame('VALUE', $chain->renderBlock('field', ['value' => 'value']));

        $sandbox->setSecurityPolicy(new SecurityPolicy(['extends', 'block']));
        $this->expectException(SecurityNotAllowedFilterError::class);

        $chain->renderBlock('field', ['value' => 'value']);
    }

    public function testSandboxPolicyChangesAreCheckedOnIntermediateParents(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends "middle" %}{% block field %}{{ parent() }}{% endblock %}',
            'middle' => '{% extends parent|upper %}',
            'GRANDPARENT' => '{% block field %}safe{% endblock %}',
        ]), ['autoescape' => false]);
        $sandbox = new SandboxExtension(new SecurityPolicy(['extends', 'block'], ['upper'], allowedFunctions: ['parent']), true);
        $twig->addExtension($sandbox);
        $chain = new BlockChain($twig, ['theme'], ['parent' => 'grandparent']);

        $this->assertSame('safe', $chain->renderBlock('field'));

        $sandbox->setSecurityPolicy(new SecurityPolicy(['extends', 'block'], allowedFunctions: ['parent']));
        $this->expectException(SecurityNotAllowedFilterError::class);

        $chain->renderBlock('field');
    }

    public function testImportedMacroNamespacesObserveSandboxPolicyChanges(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends "layout" %}{% import "macros" as macros %}{% block field %}{{ macros.label(value) }}{% endblock %}',
            'layout' => '{{ block("field") }}',
            'macros' => '{% macro label(value) %}{{ value|upper }}{% endmacro %}',
        ]), ['autoescape' => false]);
        $sandbox = new SandboxExtension(new SecurityPolicy(['extends', 'import', 'block', 'macro'], ['upper'], allowedFunctions: ['block']), true);
        $twig->addExtension($sandbox);
        $chain = new BlockChain($twig, ['theme']);

        $this->assertSame('VALUE', $twig->render('theme', ['value' => 'value']));
        $this->assertSame('VALUE', $chain->renderBlock('field', ['value' => 'value']));

        $sandbox->setSecurityPolicy(new SecurityPolicy(['extends', 'import', 'block', 'macro'], allowedFunctions: ['block']));
        $this->expectException(SecurityNotAllowedFilterError::class);

        $chain->renderBlock('field', ['value' => 'value']);
    }

    public function testProfilerKeepsTheDefiningTemplateAndBlockAttribution(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends "parent" %}{% block field %}field{% endblock %}',
            'parent' => '',
        ]), []);
        $profile = new Profile();
        $twig->addExtension(new ProfilerExtension($profile));
        $chain = new BlockChain($twig, ['theme']);

        $chain->renderBlock('field');

        $profiles = $profile->getProfiles();
        $this->assertCount(1, $profiles);
        $this->assertSame('theme', $profiles[0]->getTemplate());
        $this->assertSame(Profile::BLOCK, $profiles[0]->getType());
        $this->assertSame('field', $profiles[0]->getName());
    }

    public function testMacrosStayOwnedByTheirDefiningTemplate(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme1' => '{% macro label() %}one{% endmacro %}{% block field %}{{ _self.label() }}{% endblock %}',
            'theme2' => '{% macro label() %}two{% endmacro %}{% block field %}{{ _self.label() }}{% endblock %}',
        ]), ['autoescape' => false]);

        $chain = new BlockChain($twig, ['theme1', 'theme2']);

        $this->assertSame('one', $chain->renderBlock('field'));
    }

    public function testInheritedMacroLookupUsesTheRenderContextLineage(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends parent %}{% block field %}{{ _self.label() }}{% endblock %}',
            'parent1' => '{% macro label() %}one{% endmacro %}',
            'parent2' => '{% macro label() %}two{% endmacro %}',
        ]), ['autoescape' => false]);

        $chain = new BlockChain($twig, ['theme'], ['parent' => 'parent1']);

        $this->assertSame('one', $chain->renderBlock('field'));
        $this->assertSame('two', $chain->renderBlock('field', ['parent' => 'parent2']));
    }

    public function testSelfMacroImportsUseTheRenderContextLineage(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends parent %}{% import _self as own %}{% block field %}{{ own.label() }}{% endblock %}',
            'parent1' => '{% macro label() %}one{% endmacro %}{{ block("field") }}',
            'parent2' => '{% macro label() %}two{% endmacro %}{{ block("field") }}',
        ]), ['autoescape' => false]);
        $chain = new BlockChain($twig, ['theme'], ['parent' => 'parent1']);

        $this->assertSame('two', $twig->render('theme', ['parent' => 'parent2']));
        $this->assertSame('one', $chain->renderBlock('field'));
        $this->assertSame('two', $chain->renderBlock('field', ['parent' => 'parent2']));
    }

    public function testFromSelfImportsResolveThroughTheLineage(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends "parent" %}{% from _self import label %}{% macro wrapped() %}{{ label() }}{% endmacro %}{% block field %}{{ label() }}/{{ _self.wrapped() }}{% endblock %}',
            'parent' => '{% macro label() %}one{% endmacro %}',
        ]), ['autoescape' => false]);
        $this->assertSame('', $twig->render('theme'));

        $chain = new BlockChain($twig, ['theme']);

        $this->assertSame('one/one', $chain->renderBlock('field'));
    }

    public function testImportedMacroNamespacesKeepTheirOwnLineage(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends parent %}{% import parent as inherited %}{% block field %}{{ inherited.label() }}{% endblock %}',
            'parent1' => '{% macro label() %}one{% endmacro %}{{ block("field") }}',
            'parent2' => '{% macro label() %}two{% endmacro %}{{ block("field") }}',
        ]), ['autoescape' => false]);
        $this->assertSame('two', $twig->render('theme', ['parent' => 'parent2']));

        $chain = new BlockChain($twig, ['theme'], ['parent' => 'parent1']);

        $this->assertSame('two', $chain->renderBlock('field'));
    }

    public function testModuleImportsFollowTheDefiningTemplateBodyState(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends "layout" %}{% import helper as macros %}{% block field %}{{ macros.label() }}{% endblock %}',
            'layout' => '{{ block("field") }}',
            'macros1' => '{% macro label() %}one{% endmacro %}',
            'macros2' => '{% macro label() %}two{% endmacro %}',
        ]), ['autoescape' => false]);
        $chain = new BlockChain($twig, ['theme']);

        try {
            $chain->renderBlock('field', ['helper' => 'macros1']);
            $this->fail('Rendering an uninitialized import must fail.');
        } catch (RuntimeError) {
        }

        $this->assertSame('one', $twig->render('theme', ['helper' => 'macros1']));
        $this->assertSame('one', $chain->renderBlock('field'));
        $this->assertSame('two', $twig->render('theme', ['helper' => 'macros2']));
        $this->assertSame('two', $chain->renderBlock('field'));
    }

    public function testSelfMacroImportsInMacroBodiesResolveThroughTheLineage(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends "parent" %}{% import _self as own %}{% macro wrapped() %}{{ own.label() }}{% endmacro %}{% block field %}{{ _self.wrapped() }}{% endblock %}',
            'parent' => '{% macro label() %}one{% endmacro %}',
        ]), ['autoescape' => false]);
        $chain = new BlockChain($twig, ['theme']);

        $this->assertSame('', $twig->render('theme'));
        $this->assertSame('one', $chain->renderBlock('field'));
    }

    public function testMacroBodiesCannotResolveADynamicParentJustLikeADirectRender(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends parent %}{% import _self as own %}{% macro wrapped() %}{{ own.label() }}{% endmacro %}{% block field %}{{ _self.wrapped() }}{% endblock %}',
            'parent' => '{% macro label() %}one{% endmacro %}{{ block("field") }}',
        ]), ['autoescape' => false]);
        $chain = new BlockChain($twig, ['theme'], ['parent' => 'parent']);

        // a macro body only receives the macro arguments, so the "parent" variable is out of reach either way
        $this->expectException(RuntimeError::class);

        try {
            $twig->render('theme', ['parent' => 'parent']);
            $this->fail('Rendering the template directly must fail.');
        } catch (RuntimeError) {
        }

        $chain->renderBlock('field');
    }

    public function testMacroBodiesObserveImportUpdatesAfterConstruction(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends "layout" %}{% import helper as macros %}{% macro wrapped() %}{{ macros.label() }}{% endmacro %}{% block field %}{{ _self.wrapped() }}{% endblock %}',
            'layout' => '{{ block("field") }}',
            'macros1' => '{% macro label() %}one{% endmacro %}',
            'macros2' => '{% macro label() %}two{% endmacro %}',
        ]), ['autoescape' => false]);
        $chain = new BlockChain($twig, ['theme']);

        $this->assertSame('one', $twig->render('theme', ['helper' => 'macros1']));
        $this->assertSame('one', $chain->renderBlock('field'));
        $this->assertSame('two', $twig->render('theme', ['helper' => 'macros2']));
        $this->assertSame('two', $chain->renderBlock('field'));
    }

    public function testPreWarmedExternalMacroImportsAreNotReboundByChainOrder(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends "layout" %}{% import "macros" as macros %}{% block field %}{{ macros.label() }}{% endblock %}',
            'layout' => '{{ block("field") }}',
            'macros' => '{% extends macro_parent %}',
            'macros1' => '{% macro label() %}one{% endmacro %}',
            'macros2' => '{% macro label() %}two{% endmacro %}',
        ]), ['autoescape' => false]);
        $this->assertSame('two', $twig->render('theme', ['macro_parent' => 'macros2']));

        $context = ['macro_parent' => 'macros1'];
        $renderContext = ['macro_parent' => 'macros2'];

        $this->assertSame('two', (new BlockChain($twig, ['macros', 'theme'], $context))->renderBlock('field', $renderContext));
        $this->assertSame('two', (new BlockChain($twig, ['theme', 'macros'], $context))->renderBlock('field', $renderContext));
    }

    public function testChainsWithDifferentDynamicParentsCanBeStreamedInterleaved(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => '{% extends parent %}{% block field %}before/{{ parent() }}/after{% endblock %}',
            'parent1' => '{% block field %}one{% endblock %}',
            'parent2' => '{% block field %}two{% endblock %}',
        ]), ['autoescape' => false]);

        $stream1 = (new BlockChain($twig, ['theme'], ['parent' => 'parent1']))->streamBlock('field');
        $stream2 = (new BlockChain($twig, ['theme'], ['parent' => 'parent2']))->streamBlock('field');

        $output1 = $output2 = '';
        $stream1->rewind();
        $stream2->rewind();
        while ($stream1->valid() || $stream2->valid()) {
            if ($stream1->valid()) {
                $output1 .= $stream1->current();
                $stream1->next();
            }
            if ($stream2->valid()) {
                $output2 .= $stream2->current();
                $stream2->next();
            }
        }

        $this->assertSame('before/one/after', $output1);
        $this->assertSame('before/two/after', $output2);
    }

    public function testErrorsKeepTheDefiningSourceAndLine(): void
    {
        $twig = new Environment(new ArrayLoader([
            'theme' => "{% block field %}\n{{ missing.value }}\n{% endblock %}",
        ]), ['strict_variables' => true]);
        $chain = new BlockChain($twig, ['theme']);

        try {
            $chain->renderBlock('field');
            $this->fail('Rendering must fail.');
        } catch (RuntimeError $e) {
            $this->assertSame('theme', $e->getSourceContext()->getName());
            $this->assertSame(2, $e->getTemplateLine());
        }
    }

    public function testUnknownBlockUsesTheFirstTemplateAsErrorContext(): void
    {
        $twig = new Environment(new ArrayLoader(['theme' => '']));
        $chain = new BlockChain($twig, ['theme']);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Block "missing" on template "theme" does not exist in "theme".');

        $chain->renderBlock('missing');
    }

    public function testCircularInheritanceIsRejected(): void
    {
        $twig = new Environment(new ArrayLoader([
            'one' => '{% extends "two" %}',
            'two' => '{% extends "one" %}',
        ]));
        $chain = new BlockChain($twig, ['one']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Circular template inheritance detected while building a block chain from "one".');

        $chain->getBlockNames();
    }

    public function testRequiresAtLeastOneTemplate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A block chain requires at least one template.');

        new BlockChain(new Environment(new ArrayLoader()), []);
    }
}

class BlockChainTestTemplate extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);
        $this->parent = false;
        $this->blocks = ['field' => [$this, 'block_field']];
    }

    public function block_field(array $context, array $blocks = []): iterable
    {
        yield 'field';
    }

    public function getTemplateName(): string
    {
        return 'block_chain_test';
    }

    public function getDebugInfo(): array
    {
        return [];
    }

    public function getSourceContext(): Source
    {
        return new Source('', 'block_chain_test');
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        yield from [];
    }
}
