import GoogleRatingWidgetPlugin from './js/plugin/google-rating-widget.plugin';
import GoogleReviewsCarouselPlugin from './js/plugin/google-reviews-carousel.plugin';

const PluginManager = window.PluginManager;

PluginManager.register('SdgRatingWidget', GoogleRatingWidgetPlugin, '[data-sdg-rating-widget]');
PluginManager.register('SdgReviewsCarousel', GoogleReviewsCarouselPlugin, '[data-sdg-reviews-carousel]');
