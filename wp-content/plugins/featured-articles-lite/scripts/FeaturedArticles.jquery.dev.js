/*
	Author: Constantin Boiangiu (constantin[at]php-help.ro)
	Copyrigh (c) 2011 - author
	License: MIT(http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.opensource.org/licenses/gpl-license.php)
	Package: Wordpress Featured Articles Lite plugin
	Version: 1.0
	jQuery version: 1.4.4
	Uses: DOMMouseScroll by Brandon Aaron (http://brandonaaron.net)
*/
(function( $ ){
	
	$.fn.extend({
		FeaturedArticles: function(options){
			var opts = $.extend({}, $.fn.FeaturedArticles.defaults, options);
			return this.each(function() {
			  $this = $(this);
			  	
			  var hParams = typeof(FA_Lite_params) != 'object' ? {} : FA_Lite_params;
			  var fParams = typeof(FA_Lite_footer_params) != 'object' ? {} : FA_Lite_footer_params;
			  var p = $.extend(hParams, fParams);
			  var id = $this.attr('id'),
			  	  o = $.parseJSON(p[id].replace(/&quot;/g, "\""));
			  
			  if( o.slideDuration ){
				o.slideDuration = parseFloat(o.slideDuration)*1000; 
			  }
			  if(o.effectDuration){
			  	o.effectDuration = parseFloat(o.effectDuration)*1000;
			  }
			  
			  var opt = $.extend({}, opts, o);
			  
			  $(this).data('faSlider', opt);
			  $(this).start();
			});
		},
		
		changeSlide: function(self){
			var i = self.currentKey + 1 >= self.slides.length ? 0 : self.currentKey + 1;
            self.goToSlide(i);
		},
		
		stopSlider: function(){
			clearInterval(this.interval);
			this.interval = false;
		},
		
		startSlider: function(){
			if( this.stopped ) return;
			var o = this.settings();
			var self = this;
			if(this.interval){
				clearInterval(this.interval);
			}
			var t = function(){
				self.changeSlide(self);
			}
			this.interval = setInterval(t, o.slideDuration||3000);
		},
		
		start: function(){			
			this.slides = $(this).find('.FA_article');
			if( this.slides.length < 2 ){
				return;
			}
			var self = this;
			this.currentKey = 0;
			this.interval = false;
			this.stopped = false;
			this.prepareSlides();
			this.prepareNavigation();
			this.prepareSideNavs();
			
			var o = this.settings();
			if( o.autoSlide ){
				self.startSlider();
				$(this).mouseenter(function(){ self.stopSlider() });
				$(this).mouseleave(function(){ self.startSlider() });
			}
			
			if( !o.mouseWheelNav ) return;			
			$(this).mousewheel(function(e, delta){
				e.preventDefault();
				if (delta > 0) {
                    var key = self.currentKey - 1 < 0 ? self.slides.length - 1 : self.currentKey - 1
                } else {
                    if (delta < 0) {
                        var key = self.currentKey + 1 > self.slides.length - 1 ? 0 : self.currentKey + 1
                    }
                }
				self.stopSlider();
                self.goToSlide(key, delta);
			})
			
		},
		settings: function(e){
			var d = $(this).data('faSlider');
			return d; 
		},
		prepareSlides: function(){
			this.slides.css({
				'position': 'absolute',
				'top': 0,
				'left': 0,
				'opacity': 0,
				'z-index':1
			});
			$(this.slides[this.currentKey]).css({'opacity':1, 'z-index':100});
		},
		prepareNavigation: function(){
			this.navLinks = $(this).find('.FA_navigation a');
			if( this.navLinks.length < 1 ) return;
			var self = this,
				o = self.settings();
			
			$.each(this.navLinks, function(i, el){
				var title = $(el).parent().find('span');
				if( title.length > 0 ){
					$(el).mouseenter(function(e){
						$(title).css({'display':'block','top': -25,'opacity':0}).animate({'opacity':1,'top':-20});
					}).mouseleave(function(e){
						title.css({'display':'none','opacity':0,'top':-25});
					})
				}
				$(el).click(function(e){
					e.preventDefault();
					if ( self.interval ) {
						self.stopSlider();
					}
					self.goToSlide(i);
					if (!o.stopSlideOnClick && o.autoSlide) {
						self.startSlider();
					}else if(o.stopSlideOnClick && o.autoSlide){
						self.stopped = true;
					}	
				})
				if (i == self.currentKey) {
                    $(self.navLinks[i]).addClass("active");
                }
			})		
		},
		prepareSideNavs: function(){
			var navBack = $(this).find('.FA_back'),
				navNext = $(this).find('.FA_next'),
				self = this,
				o = self.settings();
				
			if( navBack.length > 0 ){
				$(navBack).click(function(e){
					e.preventDefault();
					var index = self.currentKey - 1 >= 0 ? self.currentKey - 1 : self.slides.length - 1;
					if ( self.interval ) {
						self.stopSlider();
					}
                	self.goToSlide(index, 1);
					if (!o.stopSlideOnClick && o.autoSlide) {
						self.startSlider();
					}else if(o.stopSlideOnClick && o.autoSlide){
						self.stopped = true;
					}
				})
			}
			if( navNext.length > 0 ){
				$(navNext).click(function(e){
					e.preventDefault();
					var index = self.currentKey + 1 < self.slides.length ? self.currentKey + 1 : 0;
					if ( self.interval ) {
						self.stopSlider();
					}
                	self.goToSlide(index);
					if (!o.stopSlideOnClick && o.autoSlide) {
						self.startSlider();
					}else if(o.stopSlideOnClick && o.autoSlide){
						self.stopped = true;
					}
				})
			}
		},
		goToSlide: function(index, direction){
			if (index == this.currentKey) {
                return;
            }
            if ( index < 0 || index >=this.slides.length ) {
                return;
            }
			
			var dir = direction||-1,
				o = this.settings(),
				fading = o.fadePosition == "left" ? "left" : "top";
				
			switch (fading) {
				case "top":
					$(this.slides[this.currentKey]).css({'top':0,'z-index':1}).animate({
						opacity: 0,
						top: dir * o.fadeDist
					},{queue:false, duration:o.effectDuration});
					$(this.slides[index]).css({'top': -dir*o.fadeDist, 'z-index':100}).animate({
						opacity: 1,
						top: 0
					},{queue:false, duration:o.effectDuration});
					break;
				case "left":
					$(this.slides[this.currentKey]).css({'left':0, 'z-index':1}).animate({
						opacity: 0,
						left: dir * o.fadeDist
					},{queue:false, duration:o.effectDuration});
					$(this.slides[index]).css({'left':-dir*o.fadeDist, 'z-index':100}).animate({
						opacity: 1,
						left: 0
					},{queue:false, duration:o.effectDuration});
				break
			}
			if (this.navLinks.length > 0) {
				$(this.navLinks[this.currentKey]).removeClass("active");
				$(this.navLinks[index]).addClass("active")
			}
			this.currentKey = index;
		}
	})
	
	$.fn.FeaturedArticles.defaults = {
		slideDuration:5000,
		effectDuration:1000,
		fadeDist:null,
		fadePosition:null,
		stopSlideOnClick: false,
		autoSlide: false,
		mouseWheelNav: false
	}	
})( jQuery );

jQuery(document).ready(function(){
	jQuery('.FA_slider').FeaturedArticles({
		
	});	
})