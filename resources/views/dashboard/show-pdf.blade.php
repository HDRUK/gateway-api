<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'sourcesans3', sans-serif;
            background: #f0f2f5;
            color: #1a1d23;
            font-size: 12px;
            padding: 20px;
        }

        .stat-num   { 
            font-size: 26px; 
            /* font-weight: bold;  */
            line-height: 1; 
        }
        .stat-label { 
            font-size: 14px; 
            color: #6b7280;
            padding: 5px 0;
        }

        .stat-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            background: #1a6b4a;
            color: #ffffff;
            font-size: 10px;
            /* font-weight: bold; */
        }

        .panel-title { 
            font-size: 18px; 
            /* font-weight: bold;  */
        }
        .list-label  { font-size: 12px; color: #1a1d23; }
        .list-num    { 
            font-size: 18px; 
            /* font-weight: bold;  */
        }
        .panel-toptitle {
            font-size: 18px;
            /* font-weight: bold; */
        }
    </style>
</head>
<body>

{{-- ── Outer wrapper ── --}}
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:1200px; margin:0 auto;">

    {{-- ══ Resources Card ══ --}}
    <tr>
        <td style="padding-bottom:14px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#ffffff; border:1px solid #e2e6ea; border-radius:8px; padding:16px;">
                <tr>
                    <td>

                        {{-- Header row --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
                            <tr>
                                <td style="vertical-align:middle;">
                                    <span class="panel-toptitle">Your resources</span>
                                </td>
                                <td style="text-align:right; color:#6b7280; font-size:11px; vertical-align:middle;">
                                    <strong>Period</strong>&nbsp;&nbsp;{{ $startDate }} – {{ $endDate }}
                                </td>
                            </tr>
                        </table>

                        {{-- Stats grid --}}
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="border-collapse:separate; border-spacing:8px;">

                            {{-- Row 1 --}}
                            <tr>
                                <td width="33.33%" style="background:#f0f2f5; border-radius:8px; padding:14px 10px 12px; text-align:center; height:100px;">
                                    <p class="stat-num">{{ $entityDatasets['total'] }}</p>
                                    <p class="stat-label">Datasets</p>
                                    @if ($entityDataUses['total_by_interval'] > 0)
                                        <p><span class="stat-badge">{{ $entityDatasets['total_by_interval'] }} added this period</span></p>
                                    @else
                                        <p>&nbsp;</p>
                                    @endif
                                </td>
                                <td width="33.33%" style="background:#f0f2f5; border-radius:8px; padding:14px 10px 12px; text-align:center; height:100px;">
                                    <p class="stat-num">{{ $entityDataUses['total'] }}</p>
                                    <p class="stat-label">Data Uses</p>
                                    @if ($entityDataUses['total_by_interval'] > 0)
                                        <p><span class="stat-badge">{{ $entityDataUses['total_by_interval'] }} added this period</span></p>
                                    @else
                                        <p>&nbsp;</p>
                                    @endif
                                </td>
                                <td width="33.33%" style="background:#f0f2f5; border-radius:8px; padding:14px 10px 12px; text-align:center; height:100px;">
                                    <p class="stat-num">{{ $entityTools['total'] }}</p>
                                    <p class="stat-label">Analysis Scripts</p>
                                    @if ($entityTools['total_by_interval'] > 0)
                                        <p><span class="stat-badge">{{ $entityTools['total_by_interval'] }} added this period</span></p>
                                    @else
                                        <p>&nbsp;</p>
                                    @endif
                                </td>
                            </tr>

                            {{-- Row 2 --}}
                            <tr>
                                <td width="33.33%" style="background:#f0f2f5; border-radius:8px; padding:14px 10px 12px; text-align:center; height:100px;">
                                    <p class="stat-num">{{ $entityPublications['total'] }}</p>
                                    <p class="stat-label">Publications</p>
                                    @if ($entityPublications['total_by_interval'] > 0)
                                        <p><span class="stat-badge">{{ $entityPublications['total_by_interval'] }} added this period</span></p>
                                    @else
                                        <p>&nbsp;</p>
                                    @endif
                                </td>
                                <td width="33.33%" style="background:#f0f2f5; border-radius:8px; padding:14px 10px 12px; text-align:center; height:100px;">
                                    <p class="stat-num">{{ $entityCollections['total'] }}</p>
                                    <p class="stat-label">Collections</p>
                                    @if ($entityCollections['total_by_interval'] > 0)
                                        <p><span class="stat-badge">{{ $entityCollections['total_by_interval'] }} added this period</span></p>
                                    @else
                                        <p>&nbsp;</p>
                                    @endif
                                </td>
                                <td width="33.33%" style="background:transparent; border:none;">&nbsp;</td>
                            </tr>

                        </table>

                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ══ Charts Row ══ --}}
    <tr>
        <td style="padding-bottom:14px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="border-collapse:separate;">
                <tr>

                    {{-- Line chart panel --}}
                    <td width="50%" style="vertical-align:top;padding-right:10px;">
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#ffffff; border:1px solid #e2e6ea; border-radius:8px; padding:16px; height:300px;">
                            <tr>
                                <td style="padding-bottom:10px;">
                                    <span class="panel-title">360 Dataset views</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="line-height:0;">
                                    {!! $lineChartSvg !!}
                                </td>
                            </tr>
                        </table>
                    </td>

                    {{-- Bar chart panel --}}
                    <td width="50%" style="vertical-align:top;padding-left:10px;">
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#ffffff; border:1px solid #e2e6ea; border-radius:8px; padding:16px; height:300px;">
                            <tr>
                                <td style="padding-bottom:10px;">
                                    <span class="panel-title">Most Dataset Views</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="line-height:0;">
                                    {!! $barChartSvg !!}
                                </td>
                            </tr>
                        </table>
                    </td>

                </tr>
            </table>
        </td>
    </tr>

    {{-- ══ Other Views + Enquiries Row ══ --}}
    <tr>
        <td>
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="border-collapse:separate;">
                <tr>

                    {{-- Other views panel --}}
                    <td width="600px" style="vertical-align:top;padding-right:10px;">
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#ffffff; border:1px solid #e2e6ea; border-radius:8px; padding:16px;">
                            <tr>
                                <td style="padding-bottom:10px;">
                                    <span class="panel-title">Other views</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table width="100%" cellpadding="0" cellspacing="0"
                                           style="border-collapse:separate; border-spacing:0 6px;">
                                        <tr>
                                            <td class="list-label"
                                                style="background:#f0f2f5; padding:10px 12px; border-radius:6px 0 0 6px;">
                                                Collections
                                            </td>
                                            <td class="list-num"
                                                style="background:#f0f2f5; padding:10px 12px; text-align:right; width:60px; border-radius:0 6px 6px 0;">
                                                {{ $collectionViews['counter'] }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="list-label"
                                                style="background:#f0f2f5; padding:10px 12px; border-radius:6px 0 0 6px;">
                                                Data Custodian page
                                            </td>
                                            <td class="list-num"
                                                style="background:#f0f2f5; padding:10px 12px; text-align:right; width:60px; border-radius:0 6px 6px 0;">
                                                {{ $dataCustodianViews['counter'] }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>

                    {{-- Enquiries panel --}}
                    <td width="600px" style="vertical-align:top;padding-left:10px;">
                        <table width="100%" cellpadding="0" cellspacing="0"
                               style="background:#ffffff; border:1px solid #e2e6ea; border-radius:8px; padding:16px;">
                            <tr>
                                <td style="padding-bottom:10px;">
                                    <span class="panel-title">Enquiries and requests</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table width="100%" cellpadding="0" cellspacing="0"
                                           style="border-collapse:separate; border-spacing:0 6px;">
                                        <tr>
                                            <td class="list-label"
                                                style="background:#f0f2f5; padding:10px 12px; border-radius:6px 0 0 6px;">
                                                General enquiries
                                            </td>
                                            <td class="list-num"
                                                style="background:#f0f2f5; padding:10px 12px; text-align:right; width:60px; border-radius:0 6px 6px 0;">
                                                {{ $entityGeneralEnquiries['total_by_interval'] }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="list-label"
                                                style="background:#f0f2f5; padding:10px 12px; border-radius:6px 0 0 6px;">
                                                Feasibility enquiries
                                            </td>
                                            <td class="list-num"
                                                style="background:#f0f2f5; padding:10px 12px; text-align:right; width:60px; border-radius:0 6px 6px 0;">
                                                {{ $entityFeasabilityEnquiries['total_by_interval'] }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="list-label"
                                                style="background:#f0f2f5; padding:10px 12px; border-radius:6px 0 0 6px;">
                                                Data Access Requests
                                            </td>
                                            <td class="list-num"
                                                style="background:#f0f2f5; padding:10px 12px; text-align:right; width:60px; border-radius:0 6px 6px 0;">
                                                {{ $entityDataAccessRequests['total_by_interval'] }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>

                </tr>
            </table>
        </td>
    </tr>

</table>

</body>
</html>