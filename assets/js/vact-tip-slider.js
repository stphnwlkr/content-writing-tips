(function () {
	'use strict';

	function initSlider(slider) {
		if (!slider) {
			return;
		}

		var items   = slider.querySelectorAll('.vact-tip-slider__item');
		var prevBtn = slider.querySelector('.vact-tip-slider__prev');
		var nextBtn = slider.querySelector('.vact-tip-slider__next');
		var status  = slider.querySelector('.vact-tip-slider__status');

		if (!items || items.length === 0) {
			return;
		}

		var currentIndex = 0;

		function update() {
			for (var i = 0; i < items.length; i++) {
				if (i === currentIndex) {
					items[i].classList.add('vact-tip--active');
				} else {
					items[i].classList.remove('vact-tip--active');
				}
			}

			if (status) {
				status.textContent = 'Tip ' + (currentIndex + 1) + ' of ' + items.length;
			}

			if (prevBtn) {
				prevBtn.disabled = (currentIndex === 0);
			}
			if (nextBtn) {
				nextBtn.disabled = (currentIndex === items.length - 1);
			}
		}

		if (prevBtn) {
			prevBtn.addEventListener('click', function () {
				if (currentIndex > 0) {
					currentIndex--;
					update();
				}
			});
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				if (currentIndex < items.length - 1) {
					currentIndex++;
					update();
				}
			});
		}

		// Initialize display.
		update();
	}

	function initAllSliders() {
		var sliders = document.querySelectorAll('.vact-tip-slider');
		if (!sliders || sliders.length === 0) {
			return;
		}

		for (var i = 0; i < sliders.length; i++) {
			initSlider(sliders[i]);
		}
	}

	// Run when DOM is ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAllSliders);
	} else {
		initAllSliders();
	}
})();