<?php

declare(strict_types=1);

pest()->group('architecture');

arch()->expect('App')->not->toUse(['die', 'dd', 'dump']);
arch()->preset()->security()->ignoring('md5');
arch()->preset()->php();
// arch()->preset()->laravel();
