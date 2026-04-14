import Plugin from 'src/plugin-system/plugin.class';
import PseudoModalUtil from 'src/utility/modal-extension/pseudo-modal.util';

export default class GoogleReviewsCarouselPlugin extends Plugin {
    init() {
        this._track = this.el.querySelector('.sdg-reviews-carousel__track');
        this._prevBtn = this.el.querySelector('.sdg-reviews-carousel__nav--prev');
        this._nextBtn = this.el.querySelector('.sdg-reviews-carousel__nav--next');

        if (!this._track) {
            return;
        }

        this._scrollAmount = 316;

        this._registerEvents();
    }

    _registerEvents() {
        if (this._prevBtn) {
            this._prevBtn.addEventListener('click', this._onPrevClick.bind(this));
        }

        if (this._nextBtn) {
            this._nextBtn.addEventListener('click', this._onNextClick.bind(this));
        }

        this.el.querySelectorAll('[data-sdg-read-more]').forEach((btn) => {
            btn.addEventListener('click', this._onReadMore.bind(this));
        });
    }

    _onPrevClick() {
        this._track.scrollBy({
            left: -this._scrollAmount,
            behavior: 'smooth',
        });
    }

    _onNextClick() {
        this._track.scrollBy({
            left: this._scrollAmount,
            behavior: 'smooth',
        });
    }

    _onReadMore(event) {
        event.stopPropagation();
        const btn = event.currentTarget;
        const reviewEl = btn.closest('.sdg-reviews-carousel__item');

        if (!reviewEl) {
            return;
        }

        const author = reviewEl.dataset.sdgAuthor || '';
        const rating = parseInt(reviewEl.dataset.sdgRating || '5', 10);
        const time = reviewEl.dataset.sdgTime || '';
        const photo = reviewEl.dataset.sdgPhoto || '';
        const fullText = btn.dataset.sdgReadMore || '';

        const stars = Array.from({length: 5}, (_, i) =>
            `<span style="color:${i < rating ? '#fbbc05' : '#ddd'};font-size:18px;">&#9733;</span>`
        ).join('');

        const avatarHtml = photo
            ? `<img src="${photo}" alt="${author}" style="width:44px;height:44px;border-radius:50%;object-fit:cover;">`
            : `<div style="width:44px;height:44px;border-radius:50%;background:#4285f4;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:18px;">${author.charAt(0).toUpperCase()}</div>`;

        const content = `
            <div class="js-pseudo-modal-template">
                <div class="modal fade" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header" style="border-bottom:1px solid #eee;">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    ${avatarHtml}
                                    <div>
                                        <strong style="font-size:15px;color:#222;">${author}</strong>
                                        <div style="font-size:12px;color:#999;">${time}</div>
                                    </div>
                                </div>
                                <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" style="padding:20px 24px;">
                                <div style="margin-bottom:12px;">${stars}</div>
                                <p style="font-size:14px;line-height:1.7;color:#333;margin:0;">${fullText}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const pseudoModal = new PseudoModalUtil(content);
        pseudoModal.open();
    }
}
