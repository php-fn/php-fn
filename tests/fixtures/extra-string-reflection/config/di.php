<?php

namespace DI;

return [
    'file' => __FILE__,
    \SplFileInfo::class => autowire()->constructor(get('file'))
];
