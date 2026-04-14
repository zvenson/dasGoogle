import Plugin from 'src/plugin-system/plugin.class';
import PseudoModalUtil from 'src/utility/modal-extension/pseudo-modal.util';

export default class GoogleRatingWidgetPlugin extends Plugin {
    static options = {
        position: 'right',
        googleUrl: '',
    };

    init() {
        this._badge = this.el.querySelector('.sdg-widget__badge');
        this._panel = this.el.querySelector('.sdg-widget__panel');
        this._closeBtn = this.el.querySelector('.sdg-widget__close');
        this._isOpen = false;

        this._registerEvents();
    }

    _registerEvents() {
        if (this._badge) {
            this._badge.addEventListener('click', this._onBadgeClick.bind(this));
        }

        if (this._closeBtn) {
            this._closeBtn.addEventListener('click', this._onCloseClick.bind(this));
        }

        document.addEventListener('click', this._onDocumentClick.bind(this));

        // "Mehr lesen" Buttons
        this.el.querySelectorAll('[data-sdg-read-more]').forEach((btn) => {
            btn.addEventListener('click', this._onReadMore.bind(this));
        });
    }

    _onBadgeClick(event) {
        event.stopPropagation();

        if (this._isOpen) {
            this._close();
        } else {
            this._open();
        }
    }

    _onCloseClick(event) {
        event.stopPropagation();
        this._close();
    }

    _onDocumentClick(event) {
        if (this._isOpen && !this.el.contains(event.target)) {
            this._close();
        }
    }

    _onReadMore(event) {
        event.stopPropagation();
        const btn = event.currentTarget;
        const reviewEl = btn.closest('.sdg-widget__review') || btn.closest('.sdg-reviews-carousel__item');

        if (!reviewEl) {
            return;
        }

        const author = reviewEl.dataset.sdgAuthor || '';
        const rating = parseInt(reviewEl.dataset.sdgRating || '5', 10);
        const time = reviewEl.dataset.sdgTime || '';
        const photo = reviewEl.dataset.sdgPhoto || '';
        const fullText = btn.dataset.sdgReadMore || '';

        this._openReviewModal(author, rating, time, photo, fullText);
    }

    _openReviewModal(author, rating, time, photo, text) {
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
                                <p style="font-size:14px;line-height:1.7;color:#333;margin:0;">${text}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const pseudoModal = new PseudoModalUtil(content);
        pseudoModal.open();
    }

    _open() {
        this._isOpen = true;
        this.el.classList.add('is-open');
    }

    _close() {
        this._isOpen = false;
        this.el.classList.remove('is-open');
    }
}
