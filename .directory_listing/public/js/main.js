$(document).ready(function() {
	$(['prev', 'next']).each(function() {
		var noun = this;
		$.fn[noun + 'With'] = function(selector) {
			var results = $();
			$(this).each(function() {
				var that = $(this)[noun]();
				while (that.length)
				{
					if (that.is(selector))
					{
						results = results.add(that);
						break;
					}
					that = that[noun]();
				}
			});
			return results;
		};
	});

	Array.prototype.diff = function(a) {
		return this.filter(function(i) {
			return a.indexOf(i) < 0;
		});
	};

	var defaultOrder = [];
	$('.pill').each(function() {
		defaultOrder.push($(this).attr('id'));
	});

	var searchTimeout = false,
		enterPressed = false,
        adminKeyPressed = false;
	$(document).helpfulKeypress(function(e) {
        var target = $(e.target);

        if (e.keyCode == 27)
            $('#search-text').val('').blur();

        if (target.is('body'))
        {
            if (e.ascii)
                $('#search-text').focus();
        }

        if (searchTimeout)
    		clearTimeout(searchTimeout);
    	searchTimeout = setTimeout(function() {
    		$(document).trigger('search-results');
    	}, 100);

        if (e.keyCode == 38 || e.keyCode == 40)
        {
        	$(document).trigger('do-selection', [e.keyCode - 39]);
            e.preventDefault();
        }
        else if (e.keyCode == 13)
        {
            adminKeyPressed = e.altKey || e.ctrlKey;
        	enterPressed = true;
        }
    });

    $(document).on('click', '#toggle-hidden', function() {
        $(this).toggleClass('active');
        $('.pill.fs-hidden').toggleClass('show-hidden', $(this).hasClass('active'));
        $(document).trigger('search-results', [true]);
    });

    $(document).on('postion-pills', function() {
        var pills = $($('.pill').get().reverse());
        pills.each(function() {
            $(this).css($(this).position()).css('position', 'absolute');
        });
    });

    $(document).on('do-selection', function(e, direction) {
        var selected = $('.pill.selected');
        if (!selected.length)
        {
            selected = $('.pill[data-order="0"]');
            while (selected.height() == 0)
                selected = $('.pill[data-order="' + (parseInt(selected.attr('data-order')) + 1) + '"]');
            selected.addClass('selected');
        }
        else if (direction && selected.length)
        {
            var next = selected;
            while (next.length && (next.get(0) == selected.get(0) || next.height() == 0))
                next = $('.pill[data-order="' + (parseInt(next.attr('data-order')) + direction) + '"]');

            if (next.length)
            {
                selected.removeClass('selected');
                next.addClass('selected');

                var nextTop = next.offset().top,
                    $window = $(window),
                    windowViewport = {
                        top: $window.scrollTop(),
                        bottom: $window.scrollTop() + $window.height()
                    };

                if (nextTop < windowViewport.top)
                    $window.scrollTop(windowViewport.top - 120);
                else if ((nextTop + next.height()) > windowViewport.bottom)
                    $window.scrollTop(windowViewport.top + 120);
            }
        }
    });

	$(document).on('search-results', function(e, override) {
    	var target = $('#search-text');

    	if (override || target.val() != target.data('value'))
        {
        	var pills = $('.pills'),
        		val = target.val().toLowerCase(),
        		terms = val.split(' ');

            $('#pill-return').toggleClass('hide', val != '');
            $('.pill.selected').removeClass('selected');

        	if (val == '')
        	{
                var cnt = 0, lastTop;
        		$(defaultOrder).each(function(ndx) {
                    var obj = $('#' + this).removeClass('hide');
                    obj.attr('data-order', ndx);
                    if (obj.is('.fs-hidden:not(.show-hidden)'))
                        obj.css('top', lastTop);
                    else
                        obj.css('top', lastTop = (cnt++) * pillHeight);
        		});
        	}
        	else
        	{
        		var scores = [];
            	$('.pill:not(#pill-return)').each(function() {
            		var that = $(this),
            			obj = {score:0, id: that.attr('id'), name: that.data('name') + ''},
            			tags = that.data('tags').split(','),
            			tagCnt = tags.length,
            			clean = that.data('clean') + '';

            		obj.score += tagCnt - tags.diff(terms).length;
            		$(terms).each(function() {
            			var term = this;
            			if (clean.indexOf(term) >= 0)
            				obj.score++;
            			$(tags).each(function() {
            				if (this.indexOf(term) == 0)
            					obj.score++;
            			});
            		});
            		if (clean.indexOf(val) == 0)
            			obj.score++;

            		if (clean == val)
            			obj.score += 50;
            		else if (clean.indexOf(val) >= 0)
            			obj.score + 20;
            		scores.push(obj);
            	});

            	scores.sort(function(a, b) {
            		if (a.score > b.score)
            			return -1;
            		if (a.score < b.score)
            			return 1;
            		return a.name.localeCompare(b.name);
            	});

                var cnt = 0, lastTop, order = $('#pill-return').length;
            	$(scores).each(function(ndx) {
                    var obj = $('#' + this.id);
                    if (this.score > 0)
                    {
                        obj.removeClass('hide');
                        if (obj.is('.fs-hidden:not(.show-hidden)'))
                            obj.css('top', lastTop);
                        else
                            obj.css('top', lastTop = (cnt++) * pillHeight);
                        obj.attr('data-order', order++);
                    }
                    else
                    {
                        obj.addClass('hide').removeAttr('data-order');
                    }
            	});
        	}

        	target.data('value', target.val());
        }
        
        if (enterPressed)
        {
            $(document).trigger('do-selection');
            var selected = $('.pill.selected');
            if (selected.length)
            {
                var nameEntry = selected.find('.name-entry'),
                    url = nameEntry.attr('href');
                if (adminKeyPressed)
                    url += nameEntry.attr('data-admin');
        	    location.href = url;
            }

        	enterPressed = false;
        }

        searchTimeout = false;
    });

    $(document).on('calc-sizes', function() {
        var data = {
            dirsize: 1,
            list: {}
        };
        $('.pill.is-dir:not(.has-size)').each(function() {
            data.list[$(this).attr('id')] = pathPrefix + $(this).data('name');
        });

        $.ajax(BASEURL, {
            data    : data,
            dataType: 'json',
            type    : 'post',
            success : function (resp) {
                for (var i in resp)
                {
                    var pill = $('#' + i);
                    pill.find('.size').html(resp[i]);
                    pill.addClass('has-size');
                }

                if ($('.pill.is-dir:not(.has-size)').length)
                    $(document).trigger('calc-sizes');
            }
        });
    });

    var pillHeight = $('.pill:not(.fs-hidden):first').outerHeight() || 39;
    $(document).trigger('postion-pills');
    $(document).trigger('search-results');
    $(document).trigger('calc-sizes');
});