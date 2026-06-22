/*
 * Erpxolluxn field_staff mobile attendance handler.
 * Captures geolocation and submits attendance actions through secure AJAX.
 */
(function ($) {
    'use strict';

    var selectors = {
        form: '#attendance-form',
        notes: '#notes, textarea[name="notes"]',
        feedback: '#attendance-feedback',
        status: '#clock-status',
        btnIn: '#btn-clock-in, [data-clock-action="in"]',
        btnOut: '#btn-clock-out, [data-clock-action="out"]'
    };

    function getForm() {
        return $(selectors.form).first();
    }

    function ensureHiddenInput($form, fieldName) {
        var $input = $form.find('input[name="' + fieldName + '"]');

        if ($input.length === 0) {
            $input = $('<input>', {
                type: 'hidden',
                name: fieldName,
                id: fieldName
            });
            $form.append($input);
        }

        return $input;
    }

    function normalizeAction(action) {
        action = (action || '').toString().toLowerCase();

        if (action === 'clock_in') {
            return 'in';
        }

        if (action === 'clock_out') {
            return 'out';
        }

        return action === 'out' ? 'out' : 'in';
    }

    function resolveEndpoint($form) {
        var endpoint = $form.data('endpoint') || $form.attr('action') || 'field_staff/clock_action';
        return endpoint;
    }

    function showFeedback(type, message) {
        var $feedback = $(selectors.feedback).first();

        if ($feedback.length > 0) {
            $feedback
                .removeClass('d-none alert-success alert-danger alert-warning alert-info')
                .addClass('alert-' + type)
                .text(message);
            return;
        }

        window.alert(message);
    }

    function setBusyState(isBusy) {
        $(selectors.btnIn).prop('disabled', isBusy).toggleClass('disabled', isBusy);
        $(selectors.btnOut).prop('disabled', isBusy).toggleClass('disabled', isBusy);
    }

    function setStatus(action) {
        var $status = $(selectors.status).first();
        if ($status.length === 0) {
            return;
        }

        if (action === 'in') {
            $status.removeClass('bg-secondary').addClass('bg-success').text('Clocked In');
        } else {
            $status.removeClass('bg-success').addClass('bg-secondary').text('Clocked Out');
        }
    }

    function injectCoordinates($form, action, latitude, longitude) {
        var lat = Number(latitude).toFixed(8);
        var lng = Number(longitude).toFixed(8);

        ensureHiddenInput($form, 'latitude').val(lat);
        ensureHiddenInput($form, 'longitude').val(lng);

        if (action === 'in') {
            ensureHiddenInput($form, 'in_latitude').val(lat);
            ensureHiddenInput($form, 'in_longitude').val(lng);
        } else {
            ensureHiddenInput($form, 'out_latitude').val(lat);
            ensureHiddenInput($form, 'out_longitude').val(lng);
        }
    }

    function collectPayload($form, action) {
        var payload = {};
        var serialized = $form.serializeArray();
        var notes = $(selectors.notes).first().val();

        $.each(serialized, function (_, item) {
            payload[item.name] = item.value;
        });

        payload.action = action;
        payload.action_type = action === 'in' ? 'clock_in' : 'clock_out';
        payload.notes = notes || '';

        // Optional meta token support for interfaces that expose CSRF keys in metadata.
        var csrfTokenName = $('meta[name="csrf-token-name"]').attr('content');
        var csrfTokenValue = $('meta[name="csrf-token-value"]').attr('content');
        if (csrfTokenName && csrfTokenValue && !payload[csrfTokenName]) {
            payload[csrfTokenName] = csrfTokenValue;
        }

        return payload;
    }

    function geolocationErrorMessage(error) {
        var defaultMessage = 'Unable to get your current location. Please verify device location settings and try again.';

        if (!error || typeof error.code === 'undefined') {
            return defaultMessage;
        }

        if (error.code === error.PERMISSION_DENIED) {
            return 'Location permission was denied. Enable location access to continue attendance tracking.';
        }

        if (error.code === error.POSITION_UNAVAILABLE) {
            return 'Location signal is currently unavailable. Move to a clearer area and try again.';
        }

        if (error.code === error.TIMEOUT) {
            return 'Location request timed out. Please retry.';
        }

        return defaultMessage;
    }

    function submitClockAction(action) {
        var normalizedAction = normalizeAction(action);
        var $form = getForm();

        if ($form.length === 0) {
            showFeedback('danger', 'Attendance form is not available in this interface.');
            return;
        }

        if (!navigator.geolocation || typeof navigator.geolocation.getCurrentPosition !== 'function') {
            showFeedback('warning', 'Location services are unavailable on this device.');
            return;
        }

        setBusyState(true);
        showFeedback('info', 'Requesting precise location access...');

        navigator.geolocation.getCurrentPosition(
            function (position) {
                injectCoordinates($form, normalizedAction, position.coords.latitude, position.coords.longitude);

                var payload = collectPayload($form, normalizedAction);
                $.ajax({
                    url: resolveEndpoint($form),
                    method: 'POST',
                    dataType: 'json',
                    data: payload,
                    timeout: 20000,
                    cache: false
                }).done(function (response) {
                    if (response && response.success) {
                        setStatus(normalizedAction);
                        showFeedback('success', response.message || 'Attendance was saved successfully.');
                    } else {
                        showFeedback('danger', (response && response.message) ? response.message : 'Attendance could not be saved right now.');
                    }
                }).fail(function (xhr, textStatus) {
                    var message = 'Network timeout while saving attendance. Please retry.';

                    if (textStatus !== 'timeout') {
                        message = 'Attendance request failed due to a network issue. Please retry.';
                    }

                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    showFeedback('danger', message);
                }).always(function () {
                    setBusyState(false);
                });
            },
            function (error) {
                showFeedback('danger', geolocationErrorMessage(error));
                setBusyState(false);
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    }

    $(document).on('click', selectors.btnIn, function (event) {
        event.preventDefault();
        submitClockAction('in');
    });

    $(document).on('click', selectors.btnOut, function (event) {
        event.preventDefault();
        submitClockAction('out');
    });
})(jQuery);
