import { Modal } from 'bootstrap';

document.addEventListener('livewire:init', () => {
	Livewire.on('show-user-modal', ({ modal }) => {
		const element = document.getElementById(modal);
		if (element) Modal.getOrCreateInstance(element).show();
	});
});

const menuToggle = document.querySelector('#menu-toggle');
const sidebar = document.querySelector('#sidebar');

document.addEventListener('click', (event) => {
	const trigger = event.target.closest('[data-open-create]');
	if (!trigger) return;
	event.preventDefault();
	document.querySelector('#users-table [wire\\:click="openCreate"]')?.click();
});

menuToggle?.addEventListener('click', () => sidebar?.classList.toggle('is-open'));

document.querySelectorAll('.nav-link').forEach((link) => {
	link.addEventListener('click', () => sidebar?.classList.remove('is-open'));
});

document.querySelectorAll('.reaction-button').forEach((button) => {
	button.addEventListener('click', () => {
		button.classList.toggle('is-liked');
		button.firstChild.textContent = button.classList.contains('is-liked') ? '♥ ' : '♡ ';
	});
});

const composer = document.querySelector('.post-placeholder');
const submitButton = document.querySelector('.composer-submit');

composer?.addEventListener('click', () => {
	const text = window.prompt('Co chcesz przekazać ekipie?');
	if (text?.trim()) {
		composer.textContent = text.trim();
		composer.classList.add('has-content');
		if (submitButton) submitButton.disabled = false;
	}
});

submitButton?.addEventListener('click', () => {
	if (!composer?.classList.contains('has-content')) return;
	submitButton.textContent = 'Opublikowano';
	submitButton.disabled = true;
});
