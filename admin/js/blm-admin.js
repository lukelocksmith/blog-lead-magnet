(function($) {
    'use strict';

    // HTML escape utility — prevents XSS when inserting server data into DOM
    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    $(document).ready(function() {

        // ── Color pickers ────────────────────────────────────────────────────
        $('.blm-color-picker').wpColorPicker({
            change: function() {
                // Small delay so iris updates the input value first
                setTimeout(updateCTAPreview, 10);
            },
            clear: function() {
                setTimeout(updateCTAPreview, 10);
            }
        });

        // ── Media library ────────────────────────────────────────────────────
        $(document).on('click', '.blm-media-upload', function(e) {
            e.preventDefault();
            var $btn      = $(this);
            var targetId  = $btn.data('target');
            var previewId = $btn.data('preview');
            var removeId  = $btn.data('remove');
            var mode      = $btn.data('mode') || 'id';

            var frame = wp.media({
                title: 'Wybierz obrazek',
                button: { text: 'Użyj tego obrazka' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var url = attachment.sizes && attachment.sizes.medium
                    ? attachment.sizes.medium.url
                    : attachment.url;

                if (mode === 'url') {
                    $('#' + targetId).val(attachment.url);
                } else {
                    $('#' + targetId).val(attachment.id);
                }

                if (previewId) {
                    $('#' + previewId).html('<img src="' + url + '" style="max-width:300px;height:auto;">');
                }
                if (removeId) {
                    $('#' + removeId).show();
                }
            });

            frame.open();
        });

        $(document).on('click', '.blm-media-remove', function(e) {
            e.preventDefault();
            var $btn      = $(this);
            var targetId  = $btn.data('target');
            var previewId = $btn.data('preview');

            $('#' + targetId).val($btn.data('mode') === 'url' ? '' : '0');
            if (previewId) {
                $('#' + previewId).html('');
            }
            $btn.hide();
        });

        // ── Live CTA preview ─────────────────────────────────────────────────
        var preview        = document.getElementById('blm-cta-preview');
        var previewHeading = document.getElementById('blm-preview-heading');
        var previewBody    = document.getElementById('blm-preview-body');
        var previewBtn     = document.getElementById('blm-preview-btn');

        function updateCTAPreview() {
            if (!preview) return;

            var heading  = $('#heading').val()      || '(brak nagłówka)';
            var btnText  = $('#button_text').val()  || '(przycisk)';
            var bg       = $('#bg_color').val()     || '#f0f4ff';
            var textCol  = $('#text_color').val()   || '#1e293b';
            var btnCol   = $('#button_color').val() || '#2563eb';
            var size     = parseInt($('#text_size').val()) || 16;

            preview.style.background = bg;
            preview.style.color      = textCol;
            preview.style.fontSize   = size + 'px';

            if (previewHeading) previewHeading.textContent = heading;
            if (previewBtn) {
                previewBtn.textContent    = btnText;
                previewBtn.style.background = btnCol;
            }
        }

        if (preview) {
            $('#heading, #button_text, #text_size').on('input', updateCTAPreview);
            updateCTAPreview();
        }

        // ── Meta box accordion ───────────────────────────────────────────────
        $(document).on('click', '.blm-meta-cta-header', function(e) {
            // Don't toggle when clicking interactive elements inside header
            if ($(e.target).closest('input, a, button, label').length) return;
            var $card = $(this).closest('.blm-meta-cta-card');
            var expanded = $card.attr('data-expanded') === '1' ? '0' : '1';
            $card.attr('data-expanded', expanded);
        });

        // Color reset in meta box
        $(document).on('click', '.blm-meta-color-reset', function(e) {
            e.stopPropagation();
            var targetId = $(this).data('target');
            $('#' + targetId).val('');
            $(this).hide();
        });

        // ── Analytics per-post expand ────────────────────────────────────────
        $(document).on('click', '.blm-row-expand', function(e) {
            e.stopPropagation();
            var $btn  = $(this);
            var $tr   = $btn.closest('tr');
            var $next = $tr.next('.blm-row-posts');
            var ctaId = $btn.data('cta-id');
            var days  = $btn.data('days');

            if ($next.length) {
                $next.remove();
                $btn.attr('aria-expanded', 'false').find('.blm-chevron').css('transform', '');
                return;
            }

            // Insert placeholder row
            var $detail = $('<tr class="blm-row-posts"><td colspan="7"><div class="blm-posts-loading" style="padding:12px 16px;font-size:12px;color:var(--blm-text-muted);">Ładowanie…</div></td></tr>');
            $tr.after($detail);
            $btn.attr('aria-expanded', 'true').find('.blm-chevron').css('transform', 'rotate(180deg)');

            $.get(blm_admin.ajax_url, {
                action:  'blm_cta_post_stats',
                cta_id:  ctaId,
                days:    days,
                _wpnonce: blm_admin.nonce
            }, function(resp) {
                if (!resp.success || !resp.data.length) {
                    $detail.find('td').html('<div style="padding:12px 16px;font-size:12px;color:var(--blm-text-muted);">Brak danych dla tego CTA.</div>');
                    return;
                }

                var rows = resp.data.map(function(p) {
                    var ctr     = p.impressions > 0 ? (p.clicks / p.impressions * 100).toFixed(1) : '0';
                    var barPct  = Math.min(100, ctr * 4);
                    // Escape HTML to prevent XSS from post titles/URLs
                    var safeTitle = escHtml(p.title);
                    var title   = p.url
                        ? '<a href="' + escHtml(p.url) + '" target="_blank" style="color:var(--blm-primary);text-decoration:none;">' + safeTitle + '</a>'
                        : safeTitle;
                    return '<tr style="background:var(--blm-bg-muted);">'
                        + '<td colspan="4" style="padding:8px 16px 8px 36px;font-size:12px;">' + title + '</td>'
                        + '<td style="font-size:12px;font-weight:500;">' + parseInt(p.impressions) + '</td>'
                        + '<td style="font-size:12px;font-weight:500;">' + parseInt(p.clicks) + '</td>'
                        + '<td><div style="display:flex;align-items:center;gap:6px;">'
                        + '<span style="font-size:12px;font-weight:600;min-width:34px;">' + ctr + '%</span>'
                        + '<div style="flex:1;height:3px;background:var(--blm-bg-subtle);border-radius:2px;overflow:hidden;min-width:32px;">'
                        + '<div style="height:100%;width:' + barPct + '%;background:var(--blm-primary);border-radius:2px;"></div>'
                        + '</div></div></td>'
                        + '</tr>';
                });

                $detail.replaceWith(rows.join(''));
            });
        });

        // Row click delegates to expand button
        $(document).on('click', 'tr[data-cta-id]', function() {
            $(this).find('.blm-row-expand').trigger('click');
        });

    });

})(jQuery);
