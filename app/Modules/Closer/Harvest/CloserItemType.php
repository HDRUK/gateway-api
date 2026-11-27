<?php

namespace App\Modules\Closer\Harvest;

/**
 * Colectica item type GUIDs, as used in CLOSER typed sets. Confirmed against
 * CLOSER on 2026-11-26 by fetching one item of each type.
 */
final class CloserItemType
{
    public const STUDY_UNIT = '30ea0200-7121-4f01-8d21-a931a182b86d';
    public const PHYSICAL_INSTANCE = 'a51e85bb-6259-4488-8df2-f08cb43485f8';
    public const VARIABLE = '683889c6-f74b-4d5e-92ed-908c0a42bb2d';
    public const VARIABLE_STATISTICS = '3b438f9f-e039-4eac-a06d-3fa1aedf48bb';
    public const QUESTION_ITEM = 'a1bb19bd-a24a-4443-8728-a6ad80eb42b8';
    public const CODE_LIST = '8b108ef8-b642-4484-9c49-f88e4bf7cf1d';
    public const CATEGORY = '7e47c269-bcab-40f7-a778-af7bbc4e3d00';
}
