<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WidgetAnalytic extends Model
{
    public const UPDATED_AT = null;

    public const EVENT_PAGE_VIEW      = 'page_view';
    public const EVENT_WIDGET_CREATED = 'widget_created';
    public const EVENT_CODE_COPIED    = 'code_copied';
    public const EVENT_WIDGET_LOAD    = 'widget_load';
    public const EVENT_GATEWAY_CLICK  = 'gateway_click';
    public const EVENT_SEARCH         = 'search';

    public const FRONTEND_EVENTS = [
        self::EVENT_PAGE_VIEW,
        self::EVENT_CODE_COPIED,
        self::EVENT_GATEWAY_CLICK,
        self::EVENT_SEARCH,
    ];

    protected $table = 'widget_analytics';

    protected $fillable = [
        'widget_id',
        'team_id',
        'event_type',
        'entity_id',
        'entity_type',
        'source_domain',
    ];

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
