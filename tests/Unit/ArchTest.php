<?php

declare(strict_types=1);

arch('every source file declares strict types')
    ->expect('Beziworld\BaseLinker')
    ->toUseStrictTypes();

arch('no debugging statements are left behind')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die'])
    ->not->toBeUsed();

arch('data objects are immutable')
    ->expect('Beziworld\BaseLinker\Data')
    ->toBeReadonly();

arch('exceptions share the package base exception')
    ->expect('Beziworld\BaseLinker\Exceptions')
    ->toExtend('Beziworld\BaseLinker\Exceptions\BaseLinkerException')
    ->ignoring('Beziworld\BaseLinker\Exceptions\BaseLinkerException');
