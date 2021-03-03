<?php
namespace App;


use Brace\Assets\AssetsModule;
use Brace\Core\AppLoader;
use Brace\Core\BraceApp;
use Brace\UiKit\Base\Element\Button;
use Brace\UiKit\Bootstrap\BootstrapConfig;
use Brace\UiKit\Bootstrap\BootstrapModule;
use Phore\Di\Container\Producer\DiService;


AppLoader::extend(function (BraceApp $app) {
    $app->addModule(new AssetsModule());
    $app->addModule(new BootstrapModule());
    $app->define("bootstrapConfig", new DiService(function () {
        $bs = new BootstrapConfig();
        $bs->brandName = "Rudl GitDb";
        $bs->title = "Rudl GitDb";
        $bs->topNav->addElement(new Button("Status", "", "/"))
            ->addElement(new Button("Secrets", "", "/secrets"));
        $bs->jsLinkFooter[] = "/assets/kasimir.js";
        return $bs;
    }));

});