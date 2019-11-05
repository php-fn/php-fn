#!/usr/bin/env php
<?php

use DI\ContainerBuilder;

echo call_user_func(require 'vendor/autoload.php', function(SplFileInfo $file, ContainerBuilder $builder) {
    return get_class($builder);
});
