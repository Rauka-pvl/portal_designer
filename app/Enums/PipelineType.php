<?php

namespace App\Enums;

enum PipelineType: string
{
    case Project = 'project';
    case Supply = 'supply';
    case Client = 'client';
}
