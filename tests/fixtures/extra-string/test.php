#!/usr/bin/env php
<?php

echo call_user_func(require 'vendor/autoload.php', function(SplFileInfo $file) {
    return $file->getFilename();
});
