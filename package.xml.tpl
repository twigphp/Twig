<?xml version="1.0" encoding="UTF-8"?>
<package packagerversion="1.8.0" version="2.0" xmlns="http://pear.php.net/dtd/package-2.0" xmlns:tasks="http://pear.php.net/dtd/tasks-1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://pear.php.net/dtd/tasks-1.0
    http://pear.php.net/dtd/tasks-1.0.xsd
    http://pear.php.net/dtd/package-2.0
    http://pear.php.net/dtd/package-2.0.xsd">
 <name>Twig</name>
 <channel>pear.twig-project.org</channel>
 <summary>Twig is a PHP template engine.</summary>
 <description>
   Twig is a template language for PHP, released under the new BSD license
   (code and documentation).

   Twig uses a syntax similar to the Django and Jinja template languages which
   inspired the Twig runtime environment.
 </description>
 <lead>
  <name>Fabien Potencier</name>
  <user>fabpot</user>
  <email>fabien.potencier@symfony-project.org</email>
  <active>yes</active>
 </lead>
 <lead>
  <name>Armin Ronacher</name>
  <user>armin</user>
  <email>armin.ronacher@active-4.com</email>
  <active>no</active>
 </lead>
 <date>{{ date }}</date>
 <time>{{ time }}</time>
 <version>
  <release>{{ version }}</release>
  <api>{{ api_version }}</api>
 </version>
 <stability>
  <release>{{ stability }}</release>
  <api>{{ stability }}</api>
 </stability>
 <license uri="http://www.opensource.org/licenses/bsd-license.php">BSD Style</license>
 <notes>-</notes>
 <contents>
   <dir name="/">
     <file name="AUTHORS" role="doc" />
     <file name="CHANGELOG" role="doc" />
     <file name="LICENSE" role="doc" />
     <file name="README.markdown" role="doc" />
     <dir name="lib">
      <dir name="Twig">
{{ files }}
      </dir>
     </dir>
   </dir>
 </contents>
 <dependencies>
  <required>
   <php>
    <min>5.2.4</min>
   </php>
   <pearinstaller>
    <min>1.4.0</min>
   </pearinstaller>
  </required>
 </dependencies>
 <phprelease />
</package>
