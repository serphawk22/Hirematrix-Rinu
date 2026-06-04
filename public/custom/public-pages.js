function togglePassword(inputId, button) {
  const input = document.getElementById(inputId);
  const icon = button.querySelector('i');
  if (!input || !icon) {
    return;
  }

  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  icon.classList.toggle('fa-eye', !show);
  icon.classList.toggle('fa-eye-slash', show);
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-password-target]').forEach(function (button) {
    button.addEventListener('click', function () {
      togglePassword(button.getAttribute('data-password-target'), button);
    });
  });
});
