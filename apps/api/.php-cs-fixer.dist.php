<?php
return (new PhpCsFixer\Config())->setRules(['@PSR12' => true, 'array_syntax' => ['syntax' => 'short']])->setFinder(PhpCsFixer\Finder::create()->notName('reference.php')->in([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/migrations', __DIR__.'/config'])->append([__DIR__.'/public/index.php']));
