(function(window, $) {
    var ajaxURL = $(this).attr('action');
    $(document).ready(function() {
        $('.yt-gallery').each(function() {
            //Video Element
            if($('.splash-button').length) {
                $('.splash-button').on('click', function () {
                    //ns_video-gdpr__notice-btn
                    $(this).parent('.ns_cover-image').addClass('video-played');
                    var gdprOption = $(this).attr('data-gdpr');
                    var autoplay = $(this).attr('data-url');
                    var iframeautoplay = autoplay.replace('autoplay=','autoplay=1');
                    if(gdprOption != '1'){
                        setTimeout(() => {
                            $(this).siblings(".ns_video-block").find('iframe').attr('src', iframeautoplay);
                        }, 100);
                    }
                });
            }

            //For Lightbox
            if ($('.ns_lightbox-button').length) {
                $('.ns_lightbox-button').on('click', function () {
                    var iframeUrl = $(this).attr('data-url');
                    var gdprOption = $(this).attr('data-gdpr');
                    var iframePlayurl = iframeUrl.replace('autoplay=','autoplay=1');
                    if(gdprOption != '1'){
                        setTimeout(() => {
                            $('.fancybox-iframe').attr('src', iframePlayurl);
                        }, 100);
                    }
                });   
            }

            var $container = $(this);

            var $iframe = $(this).find('iframe').first();
            var pageId = $('.pId').val();

            // var initSrc = $iframe[0].dataset.src;
            var initSrc = $iframe.attr('src');
            if (!initSrc) {
                initSrc = $iframe.data('ep-src');
            }

            // var firstId = $(this).find('.yt-gallery-list .yt-gallery-thumb').first().data('videoid');
            var src = initSrc.match(/[^/]*$/)[0];
            var firstId = src.split('?')[0];

            initSrc = initSrc.replace(firstId, 'GALLERYVIDEOID');
            $iframe.data('ep-gallerysrc', initSrc);

            var $listgallery = $container.find('.yt-gallery-list');

            $container.on('click', '.yt-gallery-list .yt-gallery-thumb', function() {
                $container.find('.yt-gallery-list .yt-gallery-allthumbs .yt-gallery-thumb').removeClass('yt-current-video');
                $(this).addClass('yt-current-video');
                var vid = $(this).data('videoid');
                $container.data('currvid', vid);

                var vidSrc = $iframe.data('ep-gallerysrc').replace('GALLERYVIDEOID', vid);
                var thumbplay = $container.find('.yt-pagebutton').first().data('thumbplay');
                
                if (thumbplay !== '0' && thumbplay !== 0) {
                    if (vidSrc.indexOf('autoplay') > 0) {
                        vidSrc = vidSrc.replace('autoplay=0', 'autoplay=1');
                    } else {
                        vidSrc += '&autoplay=1';
                    }

                    $iframe.addClass('yt-thumbplay');
                }
                $('html, body').animate({
                    scrollTop: $iframe.offset().top - parseInt(100)
                }, 500, function() {
                    $iframe.attr('src', vidSrc);
                    $iframe.get(0).ytsetupdone = false;
                });
            }).on('keydown', '.yt-gallery-list .yt-gallery-thumb', function(e) {
                var code = e.which;
                if ((code === 13) || (code === 32)) {
                    e.preventDefault();
                    $(this).click();

                }
            });

            $container.on('mouseenter', '.yt-gallery-list .yt-gallery-thumb', function() {
                $(this).addClass('hover');
            });

            $container.on('mouseleave', '.yt-gallery-list .yt-gallery-thumb', function() {
                $(this).removeClass('hover');
            });

             $container.on('click', '.yt-pagebutton', function () {
                var forward = $(this).hasClass('yt-next');
                var currpage = parseInt($container.data('currpage') + "");
                currpage += forward ? 1 : -1;
                $container.data('currpage', currpage);
                $container.find('.yt-gallery-list').addClass('yt-loading');
                
                var ajaxURL = $('.ajaxURL').val();
                var dataString = 'playlistId='+$(this).data('playlistid')
                +'&pageToken='+$(this).data('pagetoken')
                +'&pageSize='+$(this).data('pagesize')
                +'&columns='+$(this).data('columns')
                +'&showTitle='+$(this).data('showtitle')
                +'&showPaging='+$(this).data('showpaging')
                +'&thumbplay='+$(this).data('thumbplay');
                $.ajax({
                    type: "POST",
                    url: ajaxURL,
                    data: dataString,
                    contentType: "application/x-www-form-urlencoded;charset=UTF-8",
                    success: function(response){
                        $container.find('.yt-gallery-list').html(response);
                        $container.find('.yt-current').each(function () {
                            $(this).text($container.data('currpage'));
                        });
                        $container.find('.yt-gallery-thumb[data-videoid="' + $container.data('currvid') + '"]').addClass('yt-current-video');

                        if ($container.find('.yt-pagebutton').first().data('autonext') == '1')
                        {
                            $container.find('.yt-gallery-thumb').first().click();
                        }
                    },
                    complete: function(){
                        $container.find('.yt-gallery-list').removeClass('yt-loading');
                        $('html, body').animate({
                            scrollTop: $container.find('.yt-gallery-list').offset().top
                        }, 500);
                    },
                    error: function(){
                        alert('Sorry, there was an error loading the next page.');
                    }
                    });
            });
        });
    });
})(window, jQuery);