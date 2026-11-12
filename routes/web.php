<?php

foreach (glob(__DIR__.'/modules/*.php') ?: [] as $moduleRoutes) {
    require $moduleRoutes;
}
