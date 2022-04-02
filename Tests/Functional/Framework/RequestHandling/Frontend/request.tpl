<?php

require '{originalRoot}typo3/sysext/core/Classes/Core/SystemEnvironmentBuilder.php';
require '{originalRoot}typo3conf/ext/oauth2_client/Tests/Functional/Framework/RequestHandling/AbstractRequestBootstrap.php';
require '{originalRoot}typo3conf/ext/oauth2_client/Tests/Functional/Framework/RequestHandling/Frontend/RequestBootstrap.php';
(new \Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling\Frontend\RequestBootstrap('{documentRoot}', '{vendorPath}', {arguments}))->executeAndOutput();

?>
