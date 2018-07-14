var toasting = {
    gettoaster: function() {
        var toaster = $('#' + defaults.toaster.id);

        if (toaster.length < 1) {
            toaster = $(defaults.toaster.template).attr('id', defaults.toaster.id).css(defaults.toaster.css).addClass(defaults.toaster['class']);

            if ((defaults.stylesheet) && (!$("link[href=" + defaults.stylesheet + "]").length)) {
                $('head').appendTo('<link rel="stylesheet" href="' + defaults.stylesheet + '">');
            }

            $(defaults.toaster.container).append(toaster);
        }

        return toaster;
    },

    notify: function(title, message, priority, timeout) {
        var $toaster = this.gettoaster();
        var $toast = $(defaults.toast.template.replace('%priority%', priority)).hide().css(defaults.toast.css).addClass(defaults.toast['class']);

        $('.title', $toast).css(defaults.toast.csst).html(title);
        $('.message', $toast).css(defaults.toast.cssm).html(message);

        if ((defaults.debug) && (window.console)) {
            console.log(toast);
        }

        $toaster.append(defaults.toast.display($toast));

        if (defaults.donotdismiss.indexOf(priority) === -1) {
            if (timeout == null) {
                var timeout = (typeof defaults.timeout === 'number') ? defaults.timeout : ((typeof defaults.timeout === 'object') && (priority in defaults.timeout)) ? defaults.timeout[priority] : 1500;
            };
            setTimeout(function() {
                defaults.toast.remove($toast, function() {
                    $toast.remove();
                });
            }, timeout);
        }
    }
};
var defaults = {
    'toaster': {
        'id': 'toaster',
        'container': 'body',
        'template': '<div></div>',
        'class': 'toaster',
        'css': {
            'position': 'fixed',
            'top': '0px',
            'width': '100%',
            'zIndex': 1051
        }
    },

    'toast': {
        'template': '<div class="alert alert-%priority% text-center" role="alert">' + '<button type="button" class="close" data-dismiss="alert">' + '<span aria-hidden="true">&times;</span>' + '<span class="sr-only">Close</span>' + '</button>' + '<span class="title"></span>: <span class="message"></span>' + '</div>',

        'css': {},
        'cssm': {},
        'csst': {
            'fontWeight': 'bold'
        },

        'fade': 'slow',

        'display': function($toast) {
            return $toast.fadeIn(defaults.toast.fade);
        },

        'remove': function($toast, callback) {
            return $toast.animate({
                opacity: '0',
                padding: '0px',
                height: '0px'
            }, {
                duration: defaults.toast.fade,
                complete: callback
            });
        }
    },

    'debug': false,
    'timeout': 6000,
    'stylesheet': null,
    'donotdismiss': []
};

$.toaster = function(options) {
    if (typeof options === 'object') {
        if ('settings' in options) {
            settings = $.extend(settings, options.settings);
        }

        var title = ('title' in options) ? options.title : 'Notice';
        var message = ('message' in options) ? options.message : null;
        var priority = ('priority' in options) ? options.priority : 'info';
        var timeout = ('timeout' in options) ? options.timeout : null;

        if (message !== null) {
            toasting.notify(title, message, priority, timeout);
        }
    }
};