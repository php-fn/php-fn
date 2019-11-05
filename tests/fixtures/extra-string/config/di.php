<?php

namespace DI;

return [
    'file' => __FILE__,
    \SplFileInfo::class => create()->constructor(get('file'))
];
