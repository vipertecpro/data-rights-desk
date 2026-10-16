<?php

namespace Vipertecpro\DataRightsDesk\Enums;

enum LanguageStatus: string
{
    case Ready = 'ready';
    case NeedsReview = 'needs_review';
    case Needed = 'needed';
}
