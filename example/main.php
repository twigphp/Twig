<?php

declare(strict_types=1);

use Psl\Async;
use Twig\Cache\FilesystemCache;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require __DIR__ . '/../vendor/autoload.php';

final class User
{
    public function getUsername(): string
    {
        return 'azjezz';
    }
}

final class Account
{
    public function getUser(): User
    {
        // simulate a lazy query, the database driver is non-blocking
        Async\sleep(0.02);
        return new User();
    }

    public function getThings(): array
    {
        // simulate a lazy query, the database driver is non-blocking
        Async\sleep(0.02);
        return ['One Thing!', 'Two Things!'];
    }
}

$loader = new FilesystemLoader(__DIR__ . '/templates');
$cache = new FilesystemCache(__DIR__ . '/cache');
$twig = new Environment($loader);
$twig->setCache($cache);

$account = new Account();

echo $twig->render('user.html.twig', ['account' => $account]);
die();

[$a, $b, $c, $d] = Async\parallel([
    static fn() => $twig->render('user.html.twig', ['account' => $account]),
    static fn() => $twig->render('user.html.twig', ['account' => $account]),
    static fn() => $twig->render('user.html.twig', ['account' => $account]),
    static fn() => $twig->render('user.html.twig', ['account' => $account]),
]);

var_dump($a, $b, $c, $d);

return 0;
