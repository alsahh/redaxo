(function($){

    $(document).ready(function() {
        var $breakpoint = {};
        $breakpoint.refreshValue = function () {
            this.value = window.getComputedStyle(document.querySelector('body'), ':before').getPropertyValue('content').replace(/"/g, '');
            this.float = window.getComputedStyle(document.querySelector('body'), ':after').getPropertyValue('content').replace(/"/g, '');
        };

        $(window).resize(function () {
            $breakpoint.refreshValue();
        }).resize();


        moveMainNavigation();
        $(window).on('resize', function(){
            (!window.requestAnimationFrame) ? setTimeout(moveMainNavigation, 300) : window.requestAnimationFrame(moveMainNavigation);
        });

        function moveMainNavigation() {
            var $mainNavigation = $('#rex-js-nav-main');
            $('.navbar-toggle').attr('data-target', '');
            if ($breakpoint.float == 'min') {
                //desktop screen - insert inside header element
                $mainNavigation.detach();
                $mainNavigation.prependTo('#rex-js-page-container');
            } else {
                //mobile screen - insert after .main-content element
                $mainNavigation.detach();
                $mainNavigation.insertAfter('#rex-start-of-page');
            }
        }

        //mobile version - open/close navigation
        $('.navbar-toggle').on('click', function(event) {
            event.preventDefault();
            $('#rex-start-of-page').toggleClass('rex-nav-main-is-visible');
            $('#rex-js-nav-main').toggleClass('rex-nav-main-is-visible');
        });

        if ($breakpoint.value == 'lg') {
            $(function(){
                $('.rex-nav-main').perfectScrollbar();
            });
        }

        var $structureContentNav = null,
            $structureContentNavHeight = null,
            $structureContentNavTopPosition = null,
            $structureContentNavTopPositionSubtract = 70 + 20 + 78,
            $structureContentSidebar = null,
            $structureContentSidebarTopPosition = null,
            $structureContentSidebarTopPositionSubtract = 70 + 20 + 78,
            $structureBreadcrumb = null,
            $structureBreadcrumbTopPosition = null,
            $structureBreadcrumbTopPositionSubtract = 70 + 20,
            $structureLanguage = null,
            $structureLanguageTopPosition = null,
            $structureLanguageTopPositionSubtract = 70 + 20;

        function structureContentNavScroll() {
            if($structureContentNavTopPosition !== null) {
                if ($(this).scrollTop() >= $structureContentNavTopPosition) {
                    $structureContentNav.addClass('rex-is-fixed');
                } else {
                    $structureContentNav.removeClass('rex-is-fixed');
                }
                structureContentNavResize();
            }
        }
        function structureContentNavResize() {
            if ($structureContentNav.length > 0 && $structureContentNav.hasClass('rex-is-fixed')) {
                $structureContentNav.css('width', $structureContentNav.parent().width());
                $structureContentNav.next().css('margin-top', $structureContentNavHeight);
            } else {
                $structureContentNav.css('width', '');
                $structureContentNav.next().css('margin-top', '');
            }
        }
        function structureContentSidebarScroll() {
            if($structureContentSidebarTopPosition !== null) {
                if ($(this).scrollTop() >= $structureContentSidebarTopPosition) {
                    $structureContentSidebar.addClass('rex-is-fixed');
                } else {
                    $structureContentSidebar.removeClass('rex-is-fixed');
                }
                structureContentSidebarResize();
            }
        }
        function structureContentSidebarResize() {
            if ($structureContentSidebar.length > 0 && $structureContentSidebar.hasClass('rex-is-fixed')) {
                $structureContentSidebar.css('width', $structureContentSidebar.parent().width());
                $structureContentSidebar.perfectScrollbar();
            } else {
                $structureContentSidebar.css('width', '');
                if($structureContentSidebar.data('perfectScrollbar') !== 'undefined') {
                    $structureContentSidebar.perfectScrollbar('destroy');
                }
            }
        }

        function structureBreadcrumbScroll() {
            if($structureBreadcrumbTopPosition !== null) {
                if ($(this).scrollTop() >= $structureBreadcrumbTopPosition) {
                    $structureBreadcrumb.addClass('rex-is-fixed');
                } else {
                    $structureBreadcrumb.removeClass('rex-is-fixed');
                }
                structureBreadcrumbResize();
            }
        }
        function structureBreadcrumbResize() {
            if ($structureBreadcrumb.length > 0 && $structureBreadcrumb.hasClass('rex-is-fixed') && $structureLanguage.length > 0) {
                $structureBreadcrumb.css('width', $structureBreadcrumb.parent().width() - $structureLanguage.outerWidth(true));
            } else {
                $structureBreadcrumb.css('width', '');
            }
        }

        function structureLanguageScroll() {
            if($structureLanguageTopPosition !== null)
                if ($(this).scrollTop() >= $structureLanguageTopPosition) {
                    $structureLanguage.addClass('rex-is-fixed');
                } else {
                    $structureLanguage.removeClass('rex-is-fixed');
                }
        }

        $(function() {
            $structureContentNav = $('#rex-js-structure-content-nav');
            $structureContentNavHeight = $structureContentNav.outerHeight(true);
            $structureContentSidebar = $('#rex-js-main-sidebar');
            $structureBreadcrumb = $('#rex-js-structure-breadcrumb');
            $structureLanguage = $('#rex-js-page-main > .rex-nav-language').first();

            $(window).on({
                scroll: function() {
                    structureContentNavScroll();
                    structureContentSidebarScroll();
                    structureBreadcrumbScroll();
                    structureLanguageScroll();
                },
                resize: function () {
                    structureContentNavResize();
                    structureContentSidebarResize();
                    structureBreadcrumbResize();
                }
            });

            if($structureContentNav.length > 0) {
                $structureContentNavTopPosition = $structureContentNav.offset().top - $structureContentNavTopPositionSubtract;
                structureContentNavResize();
            }
            if($structureContentSidebar.length > 0) {
                $structureContentSidebarTopPosition = $structureContentSidebar.offset().top - $structureContentSidebarTopPositionSubtract;
                structureContentSidebarResize();
            }
            if($structureBreadcrumb.length > 0) {
                $structureBreadcrumbTopPosition = $structureBreadcrumb.offset().top - $structureBreadcrumbTopPositionSubtract;
                structureBreadcrumbResize();
            }
            if($structureLanguage.length > 0) {
                $structureLanguageTopPosition = $structureLanguage.offset().top - $structureLanguageTopPositionSubtract;
            }
        });
    });


})(jQuery);
