<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; font-size: 13px; color: #1a1a1a; padding: 24px; background: #f4f4f0; }
        .card { background: #fff; border: 1px solid #e5e5e0; border-radius: 8px; padding: 14px 16px; margin-bottom: 14px; }
        .card-title { font-size: 13px; font-weight: bold; margin-bottom: 12px; }
        .section-title { font-size: 15px; font-weight: bold; margin-bottom: 10px; }
        .date-range { font-size: 11px; color: #888; margin-bottom: 14px; }
        .metric-grid { width: 100%; border-collapse: separate; border-spacing: 8px; margin-bottom: 4px; }
        .metric-cell { background: #f0f0ec; border-radius: 6px; padding: 10px; text-align: center; }
        .metric-num { font-size: 26px; font-weight: bold; color: #1a1a1a; }
        .metric-label { font-size: 11px; color: #666; margin-top: 3px; }
        .two-col { width: 100%; border-collapse: separate; border-spacing: 10px; margin-bottom: 14px; }
        .two-col td { vertical-align: top; width: 50%; background: #fff; border: 1px solid #e5e5e0; border-radius: 8px; padding: 14px 16px; }
        .views-table { width: 100%; border-collapse: separate; border-spacing: 0 5px; }
        .views-table td { background: #f0f0ec; padding: 8px 10px; border-radius: 6px; font-size: 13px; }
        .views-table td.val { text-align: right; font-size: 17px; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 10px; color: #aaa; text-align: center; }
    </style>
</head>
<body>

    <div class="section-title">Your resources</div>
    <p class="date-range">Period: {{ $periode[0] }} — {{ $periode[1] }}</p>

    <div class="card">
        <table class="metric-grid">
            <tr>
                <td class="metric-cell">
                    <div class="metric-num">{{ $entityDatasets['total'] }}</div>
                    <div class="metric-label">Datasets</div>
                </td>
                <td class="metric-cell">
                    <div class="metric-num">{{ $entityDataUses['total'] }}</div>
                    <div class="metric-label">Data Uses</div>
                </td>
                <td class="metric-cell">
                    <div class="metric-num">{{ $entityTools['total'] }}</div>
                    <div class="metric-label">Analysis Scripts</div>
                </td>
            </tr>
        </table>
        <table class="metric-grid">
            <tr>
                <td class="metric-cell" style="width:50%">
                    <div class="metric-num">{{ $entityPublications['total'] }}</div>
                    <div class="metric-label">Publications</div>
                </td>
                <td class="metric-cell" style="width:50%">
                    <div class="metric-num">{{ $entityCollections['total'] }}</div>
                    <div class="metric-label">Collections</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="two-col">
        <tr>
            <td>
                <div class="card-title">Dataset views</div>
                {!! $lineSvg !!}
            </td>
            <td>
                <div class="card-title">Most Dataset Views</div>
                {!! $barSvg !!}
            </td>
        </tr>
    </table>

    <table class="two-col">
        <tr>
            <td>
                <div class="card-title">Other views</div>
                <table class="views-table">
                    <tr>
                        <td>Collections</td>
                        <td class="val">{{ $collectionViews[0]['counter'] ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Data Custodian page</td>
                        <td class="val">{{ $dataCustodianViews[0]['counter'] ?? 0 }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <div class="card-title">Enquiries and requests</div>
                <table class="views-table">
                    <tr>
                        <td>General enquiries</td>
                        <td class="val">{{ $entityGeneralEnquiries['total'] }}</td>
                    </tr>
                    <tr>
                        <td>Feasibility enquiries</td>
                        <td class="val">{{ $entityFeasabilityEnquiries['total'] }}</td>
                    </tr>
                    <tr>
                        <td>Data Access Requests</td>
                        <td class="val">{{ $entityDataAccessRequests['total'] }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">Generated on {{ now()->format('d M Y, H:i') }}</div>

</body>
</html>