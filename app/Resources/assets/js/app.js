(function ($) {
    "use strict";
    
    if($('#calendar').length === 0) return;
    
    
    var options = {
        events_source: '/admin/event/data',
        view: 'month',
        tmpl_path: '/calendar_tmpls/',
        tmpl_cache: false,
        language: 'ru-RU',
        onAfterViewLoad: function (view) {
            $('.current_date_region').text(this.getTitle());
            $('.btn-group button').removeClass('active');
            $('button[data-calendar-view="' + view + '"]').addClass('active');
            $(".cal-cell").droppable({
                drop: function(event, ui){
                    var event_id = ui.draggable.find('a').data('event-id');
                    var date = $(this).find('.pull-right').data('cal-date');                    
                    $.post("/admin/event/update_date/" + event_id, {date: date}, function(data){
                        
                        for(var i in calendar.options.events)
                        {
                            if(calendar.options.events[i].id == event_id)
                            {
                                calendar.options.events[i] = data.data;
                                break;
                            }
                        }                        
                        calendar.options.events.sort(function(a, b) {
                            var delta;
                            delta = a.start - b.start;
                            if(delta == 0) {
                                delta = a.end - b.end;
                            }
                            return delta;
                        });
                        calendar.options.onAfterEventsLoad.call(calendar, calendar.options.events);                        
                        calendar._render();
                        calendar.options.onAfterViewLoad.call(calendar, calendar.options.view);                        
                    });
                },
                hoverClass: "drop-hover"
            });
        },
        onAfterEventsListShowed: function(slider){
            slider.find('li > span').draggable({
                handle:'span.move',
                revert: true,
                stop: function(event, ui){
                   var $this = $(this);
                   var id = $this.find('a').data('event-id');
                   //$this.attr('style', '');
                }
            });
        },
        classes: {
            months: {
                general: 'label'
            }
        }
    };
    var calendar = $('#calendar').calendar(options);
    $('.btn-group button[data-calendar-nav]').each(function () {
        var $this = $(this);
        $this.click(function () {
            calendar.navigate($this.data('calendar-nav'));
        });
    });
    $('.btn-group button[data-calendar-view]').each(function () {
        var $this = $(this);
        $this.click(function () {
            calendar.view($this.data('calendar-view'));
        });
    });
    $('#first_day').change(function () {
        var value = $(this).val();
        value = value.length ? parseInt(value) : null;
        calendar.setOptions({first_day: value});
        calendar.view();
    });
    $('#language').change(function () {
        calendar.setLanguage($(this).val());
        calendar.view();
    });
    $('#events-in-modal').change(function () {
        var val = $(this).is(':checked') ? $(this).val() : null;
        calendar.setOptions({modal: val});
    });
    $('#format-12-hours').change(function () {
        var val = $(this).is(':checked') ? true : false;
        calendar.setOptions({format12: val});
        calendar.view();
    });
    $('#show_wbn').change(function () {
        var val = $(this).is(':checked') ? true : false;
        calendar.setOptions({display_week_numbers: val});
        calendar.view();
    });
    $('#show_wb').change(function () {
        var val = $(this).is(':checked') ? true : false;
        calendar.setOptions({weekbox: val});
        calendar.view();
    });
    $('#events-modal .modal-header, #events-modal .modal-footer').click(function (e) {
//e.preventDefault();
//e.stopPropagation();
    });
}(jQuery));