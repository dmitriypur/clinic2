<?php

namespace App\Policies;

class FramePolicy extends FrameCatalogPolicy
{
    protected const SUBJECT = 'frame';

    protected const ALLOW_DELETE = true;
}
