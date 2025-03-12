<?php

declare (strict_types=1);

/*
 *  Besides ray we don't test for debug function use as we need to test their proper functioning in the package.
 */
it('will not use debugging functions')
    ->expect(['ray'])
    ->each->not->toBeUsed();
