<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
$is_clocked_in = isset($is_clocked_in) ? (bool) $is_clocked_in : false;
$status_label = $is_clocked_in ? 'Clocked In' : 'Clocked Out';
$status_class = $is_clocked_in ? 'bg-success' : 'bg-secondary';
$title_text = isset($title) ? htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') : 'Field Attendance Tracker';
$clock_action_url = function_exists('admin_url') ? admin_url('field_staff/clock_action') : 'field_staff/clock_action';
$attendance_records = isset($attendance_records) && is_array($attendance_records) ? $attendance_records : [];

if (!function_exists('field_staff_map_url')) {
    function field_staff_map_url($latitude, $longitude)
    {
        $latitude = trim((string) $latitude);
        $longitude = trim((string) $longitude);

        if ($latitude === '' || $longitude === '' || $latitude === '-' || $longitude === '-') {
            return '';
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;
        $offset = 0.0035;
        $left = $lng - $offset;
        $right = $lng + $offset;
        $top = $lat + $offset;
        $bottom = $lat - $offset;

        return 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode($left) . '%2C' . rawurlencode($bottom) . '%2C' . rawurlencode($right) . '%2C' . rawurlencode($top) . '&layer=mapnik&marker=' . rawurlencode($lat) . '%2C' . rawurlencode($lng);
    }
}
?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6 col-md-offset-3 col-sm-10 col-sm-offset-1 col-xs-12">
                                <div class="panel panel-default mtop10">
                                    <div class="panel-body">
                                        <div class="clearfix mtop5 mbot15">
                                            <h4 class="pull-left no-margin"><?php echo $title_text; ?></h4>
                                            <span id="clock-status" class="badge <?php echo htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8'); ?> pull-right">
                                                <?php echo htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </div>

                                        <p class="text-muted mbot20">Record field attendance with location verification in your workforce workflow.</p>
                                        <p id="gps-preview" class="text-muted mbot15">GPS: waiting for capture</p>
                                        <p class="mbot20">
                                            <a id="gps-map-btn" href="#" class="btn btn-info btn-sm disabled" aria-disabled="true" data-map-url="" data-lat="" data-lng="">Show on Map</a>
                                        </p>

                                        <form id="attendance-form" method="post" action="<?php echo htmlspecialchars($clock_action_url, ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                                            <input type="hidden" name="action" id="attendance_action" value="">
                                            <input type="hidden" name="action_type" id="attendance_action_type" value="">
                                            <input type="hidden" name="latitude" id="attendance_latitude" value="">
                                            <input type="hidden" name="longitude" id="attendance_longitude" value="">
                                            <input type="hidden" name="in_latitude" id="in_latitude" value="">
                                            <input type="hidden" name="in_longitude" id="in_longitude" value="">
                                            <input type="hidden" name="out_latitude" id="out_latitude" value="">
                                            <input type="hidden" name="out_longitude" id="out_longitude" value="">

                                            <?php if (isset($this->security) && method_exists($this->security, 'get_csrf_token_name') && method_exists($this->security, 'get_csrf_hash')) { ?>
                                                <input
                                                    type="hidden"
                                                    name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>"
                                                    value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php } ?>

                                            <div class="form-group">
                                                <label for="field_notes" class="control-label">Field Notes (Optional)</label>
                                                <textarea
                                                    id="field_notes"
                                                    name="notes"
                                                    class="form-control"
                                                    rows="4"
                                                    placeholder="Add attendance or location context"></textarea>
                                            </div>

                                            <div class="row">
                                                <div class="col-xs-12 col-sm-6 mbot10">
                                                    <button
                                                        type="button"
                                                        id="clock-in-btn"
                                                        class="btn btn-success btn-lg btn-block"
                                                        <?php echo $is_clocked_in ? 'disabled="disabled"' : ''; ?>>
                                                        <i class="fa fa-sign-in" aria-hidden="true"></i>
                                                        Clock In
                                                    </button>
                                                </div>
                                                <div class="col-xs-12 col-sm-6 mbot10">
                                                    <button
                                                        type="button"
                                                        id="clock-out-btn"
                                                        class="btn btn-danger btn-lg btn-block"
                                                        <?php echo !$is_clocked_in ? 'disabled="disabled"' : ''; ?>>
                                                        <i class="fa fa-sign-out" aria-hidden="true"></i>
                                                        Clock Out
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mtop20">
                            <div class="col-md-12">
                                <h5 class="mbot10">Attendance Ledger History</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Clock In</th>
                                                <th>Clock Out</th>
                                                <th>In GPS</th>
                                                <th>Out GPS</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($attendance_records)) { ?>
                                                <?php foreach ($attendance_records as $row) { ?>
                                                    <?php
                                                    $date = isset($row['date']) ? (string) $row['date'] : '-';
                                                    $clock_in = isset($row['clock_in']) ? (string) $row['clock_in'] : '-';
                                                    $clock_out = isset($row['clock_out']) && $row['clock_out'] !== null ? (string) $row['clock_out'] : '-';
                                                    $in_latitude = isset($row['in_latitude']) ? (string) $row['in_latitude'] : '-';
                                                    $in_longitude = isset($row['in_longitude']) ? (string) $row['in_longitude'] : '-';
                                                    $out_latitude = isset($row['out_latitude']) && $row['out_latitude'] !== null ? (string) $row['out_latitude'] : '-';
                                                    $out_longitude = isset($row['out_longitude']) && $row['out_longitude'] !== null ? (string) $row['out_longitude'] : '-';
                                                    $in_gps = $in_latitude . ', ' . $in_longitude;
                                                    $out_gps = $out_latitude . ', ' . $out_longitude;
                                                    $in_map_url = field_staff_map_url($in_latitude, $in_longitude);
                                                    $out_map_url = field_staff_map_url($out_latitude, $out_longitude);
                                                    $notes = isset($row['notes']) && trim((string) $row['notes']) !== '' ? (string) $row['notes'] : '-';
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($clock_in, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($clock_out, ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td>
                                                            <div><?php echo htmlspecialchars($in_gps, ENT_QUOTES, 'UTF-8'); ?></div>
                                                            <?php if ($in_map_url !== '') { ?>
                                                                <a href="#" data-map-url="<?php echo htmlspecialchars($in_map_url, ENT_QUOTES, 'UTF-8'); ?>" data-lat="<?php echo htmlspecialchars($in_latitude, ENT_QUOTES, 'UTF-8'); ?>" data-lng="<?php echo htmlspecialchars($in_longitude, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-info btn-xs mtop5 js-attendance-map-trigger">Show on Map</a>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <div><?php echo htmlspecialchars($out_gps, ENT_QUOTES, 'UTF-8'); ?></div>
                                                            <?php if ($out_map_url !== '') { ?>
                                                                <a href="#" data-map-url="<?php echo htmlspecialchars($out_map_url, ENT_QUOTES, 'UTF-8'); ?>" data-lat="<?php echo htmlspecialchars($out_latitude, ENT_QUOTES, 'UTF-8'); ?>" data-lng="<?php echo htmlspecialchars($out_longitude, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-info btn-xs mtop5 js-attendance-map-trigger">Show on Map</a>
                                                            <?php } ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($notes, ENT_QUOTES, 'UTF-8'); ?></td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">No attendance records available yet.</td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="attendance-map-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Attendance Location Map</h4>
            </div>
            <div class="modal-body" style="padding:0;">
                <div class="clearfix" style="padding:12px 15px 8px; border-bottom:1px solid #ececec;">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Map Layer">
                        <button type="button" id="map-layer-street" class="btn btn-default active" data-layer="street">Street</button>
                        <button type="button" id="map-layer-satellite" class="btn btn-default" data-layer="satellite">Satellite</button>
                    </div>
                    <button type="button" id="copy-map-coords" class="btn btn-info btn-sm pull-right" disabled="disabled">Copy Coordinates</button>
                    <button type="button" id="open-full-map" class="btn btn-default btn-sm pull-right mright5" disabled="disabled">Open Full Map</button>
                    <p id="map-coords-preview" class="text-muted mbot0 mtop10">Coordinates: unavailable</p>
                </div>
                <iframe
                    id="attendance-map-frame"
                    src=""
                    style="border:0; width:100%; height:420px;"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
    (function() {
        'use strict';

        function getJq() {
            if (window.jQuery && window.jQuery.fn) {
                return window.jQuery;
            }

            if (window.$ && window.$.fn) {
                return window.$;
            }

            return null;
        }

        function bootAttendance() {
            var jq = getJq();
            if (!jq) {
                console.error('jQuery is unavailable for attendance handlers.');
                return;
            }

            var $ = jq;
            var isClockedIn = <?php echo $is_clocked_in ? 'true' : 'false'; ?>;

            function notify(type, message) {
                if (typeof alert_float === 'function') {
                    alert_float(type, message);
                } else {
                    window.alert(message);
                }
            }

            function appendCsrf(payload) {
                var $csrf = $('#attendance-form input[type="hidden"]').filter(function() {
                    var name = ($(this).attr('name') || '').toLowerCase();
                    return name.indexOf('csrf') !== -1;
                }).first();

                if ($csrf.length) {
                    payload[$csrf.attr('name')] = $csrf.val();
                }

                return payload;
            }

            function escapeHtml(value) {
                return $('<div>').text(value === null || typeof value === 'undefined' ? '' : value).html();
            }

            function updateStatusDisplay() {
                $('#clock-status')
                    .removeClass('bg-success bg-secondary')
                    .addClass(isClockedIn ? 'bg-success' : 'bg-secondary')
                    .text(isClockedIn ? 'Clocked In' : 'Clocked Out');

                $('#clock-in-btn').prop('disabled', isClockedIn);
                $('#clock-out-btn').prop('disabled', !isClockedIn);
            }

            function updateMapButton(latitude, longitude) {
                var $mapBtn = $('#gps-map-btn');

                if (!$mapBtn.length) {
                    return;
                }

                if (typeof latitude === 'undefined' || typeof longitude === 'undefined' || latitude === null || longitude === null) {
                    $mapBtn.attr('href', '#').attr('data-map-url', '').attr('data-lat', '').attr('data-lng', '').addClass('disabled').attr('aria-disabled', 'true');
                    return;
                }

                var lat = parseFloat(latitude);
                var lng = parseFloat(longitude);
                var offset = 0.0035;
                var left = lng - offset;
                var right = lng + offset;
                var top = lat + offset;
                var bottom = lat - offset;
                var mapUrl = 'https://www.openstreetmap.org/export/embed.html?bbox=' +
                    encodeURIComponent(left) + '%2C' + encodeURIComponent(bottom) + '%2C' + encodeURIComponent(right) + '%2C' + encodeURIComponent(top) +
                    '&layer=mapnik&marker=' + encodeURIComponent(lat) + '%2C' + encodeURIComponent(lng);
                $mapBtn.attr('href', '#').attr('data-map-url', mapUrl).attr('data-lat', lat.toFixed(8)).attr('data-lng', lng.toFixed(8)).removeClass('disabled').removeAttr('aria-disabled');
            }

            function buildStreetMapUrl(latitude, longitude) {
                var lat = parseFloat(latitude);
                var lng = parseFloat(longitude);
                var offset = 0.0035;
                var left = lng - offset;
                var right = lng + offset;
                var top = lat + offset;
                var bottom = lat - offset;

                return 'https://www.openstreetmap.org/export/embed.html?bbox=' +
                    encodeURIComponent(left) + '%2C' + encodeURIComponent(bottom) + '%2C' + encodeURIComponent(right) + '%2C' + encodeURIComponent(top) +
                    '&layer=mapnik&marker=' + encodeURIComponent(lat) + '%2C' + encodeURIComponent(lng);
            }

            function buildSatelliteMapUrl(latitude, longitude) {
                var lat = parseFloat(latitude);
                var lng = parseFloat(longitude);

                return 'https://maps.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '&t=k&z=18&output=embed';
            }

            function buildStreetExternalMapUrl(latitude, longitude) {
                var lat = parseFloat(latitude);
                var lng = parseFloat(longitude);

                return 'https://www.openstreetmap.org/?mlat=' + encodeURIComponent(lat) + '&mlon=' + encodeURIComponent(lng) + '#map=18/' + encodeURIComponent(lat) + '/' + encodeURIComponent(lng);
            }

            function buildSatelliteExternalMapUrl(latitude, longitude) {
                var lat = parseFloat(latitude);
                var lng = parseFloat(longitude);

                return 'https://maps.google.com/?q=' + encodeURIComponent(lat + ',' + lng) + '&t=k&z=18';
            }

            function updateOpenFullMapButton(url) {
                var $btn = $('#open-full-map');
                var safeUrl = (url || '').trim();

                if (!safeUrl) {
                    $btn.prop('disabled', true).attr('data-external-url', '');
                    return;
                }

                $btn.prop('disabled', false).attr('data-external-url', safeUrl);
            }

            function updateMapLayerButtons(layer) {
                $('#map-layer-street').toggleClass('active', layer === 'street');
                $('#map-layer-satellite').toggleClass('active', layer === 'satellite');
            }

            function renderModalMap(layer) {
                var $modal = $('#attendance-map-modal');
                var latitude = parseFloat($modal.attr('data-lat') || '');
                var longitude = parseFloat($modal.attr('data-lng') || '');

                if (isNaN(latitude) || isNaN(longitude)) {
                    return;
                }

                var mapUrl = layer === 'satellite' ? buildSatelliteMapUrl(latitude, longitude) : buildStreetMapUrl(latitude, longitude);
                $modal.attr('data-layer', layer);
                updateMapLayerButtons(layer);
                updateOpenFullMapButton(layer === 'satellite' ? buildSatelliteExternalMapUrl(latitude, longitude) : buildStreetExternalMapUrl(latitude, longitude));
                $('#attendance-map-frame').attr('src', mapUrl);
            }

            function setModalCoordinates(latitude, longitude) {
                var $modal = $('#attendance-map-modal');
                var lat = parseFloat(latitude);
                var lng = parseFloat(longitude);

                if (isNaN(lat) || isNaN(lng)) {
                    $modal.attr('data-lat', '').attr('data-lng', '');
                    $('#map-coords-preview').text('Coordinates: unavailable');
                    $('#copy-map-coords').prop('disabled', true).attr('data-coords', '');
                    updateOpenFullMapButton('');
                    return;
                }

                var latText = lat.toFixed(8);
                var lngText = lng.toFixed(8);
                $modal.attr('data-lat', latText).attr('data-lng', lngText);
                $('#map-coords-preview').text('Coordinates: ' + latText + ', ' + lngText);
                $('#copy-map-coords').prop('disabled', false).attr('data-coords', latText + ', ' + lngText);
            }

            function openMapModal(mapUrl, latitude, longitude) {
                if ((!mapUrl || mapUrl === '#') && (typeof latitude === 'undefined' || typeof longitude === 'undefined')) {
                    return;
                }

                setModalCoordinates(latitude, longitude);
                var lat = parseFloat(latitude);
                var lng = parseFloat(longitude);
                if ((isNaN(lat) || isNaN(lng)) && mapUrl) {
                    updateOpenFullMapButton(mapUrl);
                    $('#attendance-map-frame').attr('src', mapUrl);
                } else {
                    renderModalMap($('#attendance-map-modal').attr('data-layer') || 'street');
                }

                $('#attendance-map-modal').modal('show');
            }

            function renderAttendanceRecord(record, actionKey) {
                if (!record) {
                    return;
                }

                var dateValue = record.date || '-';
                var clockInValue = record.clock_in || '-';
                var clockOutValue = record.clock_out || '-';
                var inGpsValue = (record.in_latitude || '-') + ', ' + (record.in_longitude || '-');
                var outGpsValue = (record.out_latitude || '-') + ', ' + (record.out_longitude || '-');
                var notesValue = record.notes || '-';
                var $tbody = $('.table-responsive table tbody').first();

                if (!$tbody.length) {
                    return;
                }

                if (actionKey === 'clock_in') {
                    var inMapUrl = record.in_latitude && record.in_longitude ?
                        'https://www.openstreetmap.org/export/embed.html?bbox=' +
                        encodeURIComponent(parseFloat(record.in_longitude) - 0.0035) + '%2C' + encodeURIComponent(parseFloat(record.in_latitude) - 0.0035) + '%2C' + encodeURIComponent(parseFloat(record.in_longitude) + 0.0035) + '%2C' + encodeURIComponent(parseFloat(record.in_latitude) + 0.0035) +
                        '&layer=mapnik&marker=' + encodeURIComponent(record.in_latitude) + '%2C' + encodeURIComponent(record.in_longitude) :
                        '';
                    var outMapUrl = record.out_latitude && record.out_longitude ?
                        'https://www.openstreetmap.org/export/embed.html?bbox=' +
                        encodeURIComponent(parseFloat(record.out_longitude) - 0.0035) + '%2C' + encodeURIComponent(parseFloat(record.out_latitude) - 0.0035) + '%2C' + encodeURIComponent(parseFloat(record.out_longitude) + 0.0035) + '%2C' + encodeURIComponent(parseFloat(record.out_latitude) + 0.0035) +
                        '&layer=mapnik&marker=' + encodeURIComponent(record.out_latitude) + '%2C' + encodeURIComponent(record.out_longitude) :
                        '';
                    var newRow = '<tr data-attendance-id="' + escapeHtml(record.id || '') + '">' +
                        '<td>' + escapeHtml(dateValue) + '</td>' +
                        '<td>' + escapeHtml(clockInValue) + '</td>' +
                        '<td>' + escapeHtml(clockOutValue) + '</td>' +
                        '<td><div>' + escapeHtml(inGpsValue) + '</div>' + (inMapUrl ? '<a href="#" data-map-url="' + escapeHtml(inMapUrl) + '" data-lat="' + escapeHtml(record.in_latitude || '') + '" data-lng="' + escapeHtml(record.in_longitude || '') + '" class="btn btn-info btn-xs mtop5 js-attendance-map-trigger">Show on Map</a>' : '') + '</td>' +
                        '<td><div>' + escapeHtml(outGpsValue) + '</div>' + (outMapUrl ? '<a href="#" data-map-url="' + escapeHtml(outMapUrl) + '" data-lat="' + escapeHtml(record.out_latitude || '') + '" data-lng="' + escapeHtml(record.out_longitude || '') + '" class="btn btn-info btn-xs mtop5 js-attendance-map-trigger">Show on Map</a>' : '') + '</td>' +
                        '<td>' + escapeHtml(notesValue) + '</td>' +
                        '</tr>';

                    $tbody.find('tr td[colspan="6"]').closest('tr').remove();
                    $tbody.prepend(newRow);
                    return;
                }

                if (actionKey === 'clock_out') {
                    var $row = $tbody.find('tr[data-attendance-id="' + record.id + '"]').first();
                    if ($row.length) {
                        var outMapUrl = record.out_latitude && record.out_longitude ?
                            'https://www.openstreetmap.org/export/embed.html?bbox=' +
                            encodeURIComponent(parseFloat(record.out_longitude) - 0.0035) + '%2C' + encodeURIComponent(parseFloat(record.out_latitude) - 0.0035) + '%2C' + encodeURIComponent(parseFloat(record.out_longitude) + 0.0035) + '%2C' + encodeURIComponent(parseFloat(record.out_latitude) + 0.0035) +
                            '&layer=mapnik&marker=' + encodeURIComponent(record.out_latitude) + '%2C' + encodeURIComponent(record.out_longitude) :
                            '';
                        $row.find('td').eq(2).html(escapeHtml(clockOutValue));
                        $row.find('td').eq(4).html('<div>' + escapeHtml(outGpsValue) + '</div>' + (outMapUrl ? '<a href="#" data-map-url="' + escapeHtml(outMapUrl) + '" data-lat="' + escapeHtml(record.out_latitude || '') + '" data-lng="' + escapeHtml(record.out_longitude || '') + '" class="btn btn-info btn-xs mtop5 js-attendance-map-trigger">Show on Map</a>' : ''));
                        $row.find('td').eq(5).html(escapeHtml(notesValue));
                    }
                }
            }

            function postAttendance(actionKey) {
                if (!navigator.geolocation || typeof navigator.geolocation.getCurrentPosition !== 'function') {
                    notify('danger', 'Location service is unavailable on this device.');
                    return;
                }

                notify('success', 'Requesting GPS location...');

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        var latitude = position.coords.latitude;
                        var longitude = position.coords.longitude;

                        $('#gps-preview').text('GPS: ' + latitude.toFixed(8) + ', ' + longitude.toFixed(8));
                        updateMapButton(latitude.toFixed(8), longitude.toFixed(8));

                        var payload = appendCsrf({
                            action: actionKey,
                            latitude: latitude,
                            longitude: longitude,
                            notes: $('#field_notes').val()
                        });

                        $.post("<?php echo admin_url('field_staff/clock_action'); ?>", payload, function(response) {
                            if (response && response.success) {
                                isClockedIn = actionKey === 'clock_in';
                                updateStatusDisplay();
                                renderAttendanceRecord(response.record || null, actionKey);
                                notify('success', response.message || 'Attendance updated successfully');
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                notify('danger', 'Server routing communication error or action denied.');
                            }
                        }, 'json').fail(function() {
                            notify('danger', 'Server routing communication error or action denied.');
                        });
                    },
                    function(error) {
                        var message = 'Unable to capture GPS location.';

                        if (error && error.code === error.PERMISSION_DENIED) {
                            message = 'Location permission denied. Please allow GPS access.';
                        } else if (error && error.code === error.POSITION_UNAVAILABLE) {
                            message = 'Location signal unavailable. Move to an open area and retry.';
                        } else if (error && error.code === error.TIMEOUT) {
                            message = 'Location request timed out. Please try again.';
                        }

                        notify('danger', message);
                    }, {
                        enableHighAccuracy: true,
                        timeout: 20000,
                        maximumAge: 0
                    }
                );
            }

            $('#clock-in-btn').click(function(e) {
                e.preventDefault();
                console.log('Button clicked, initializing tracking...');
                postAttendance('clock_in');
            });

            $('#clock-out-btn').click(function(e) {
                e.preventDefault();
                console.log('Button clicked, initializing tracking...');
                postAttendance('clock_out');
            });

            $('#gps-map-btn').click(function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) {
                    return;
                }

                openMapModal($(this).attr('data-map-url'), $(this).attr('data-lat'), $(this).attr('data-lng'));
            });

            $(document).on('click', '.js-attendance-map-trigger', function(e) {
                e.preventDefault();
                openMapModal($(this).attr('data-map-url'), $(this).attr('data-lat'), $(this).attr('data-lng'));
            });

            $('#map-layer-street, #map-layer-satellite').click(function(e) {
                e.preventDefault();
                renderModalMap($(this).attr('data-layer') || 'street');
            });

            $('#copy-map-coords').click(function() {
                var coords = ($(this).attr('data-coords') || '').trim();
                if (!coords) {
                    notify('danger', 'No coordinates available to copy.');
                    return;
                }

                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    navigator.clipboard.writeText(coords).then(function() {
                        notify('success', 'Coordinates copied.');
                    }).catch(function() {
                        notify('danger', 'Unable to copy coordinates.');
                    });
                    return;
                }

                var $temp = $('<textarea>').css({
                    position: 'fixed',
                    top: '-9999px',
                    left: '-9999px'
                }).val(coords).appendTo('body');
                $temp[0].focus();
                $temp[0].select();
                try {
                    document.execCommand('copy');
                    notify('success', 'Coordinates copied.');
                } catch (err) {
                    notify('danger', 'Unable to copy coordinates.');
                }
                $temp.remove();
            });

            $('#open-full-map').click(function() {
                var url = ($(this).attr('data-external-url') || '').trim();
                if (!url) {
                    notify('danger', 'No map link available.');
                    return;
                }

                var opened = window.open(url, '_blank', 'noopener,noreferrer');
                if (!opened) {
                    notify('danger', 'Browser blocked opening a new tab.');
                }
            });

            $('#attendance-map-modal').on('hidden.bs.modal', function() {
                $('#attendance-map-frame').attr('src', '');
                $('#attendance-map-modal').attr('data-lat', '').attr('data-lng', '').attr('data-layer', 'street');
                updateMapLayerButtons('street');
                $('#map-coords-preview').text('Coordinates: unavailable');
                $('#copy-map-coords').prop('disabled', true).attr('data-coords', '');
                updateOpenFullMapButton('');
            });

            updateStatusDisplay();
            updateMapButton(null, null);
            $('#attendance-map-modal').attr('data-layer', 'street');
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootAttendance);
        } else {
            bootAttendance();
        }
    })();
</script>