// Logout confirmation modal
document.addEventListener('DOMContentLoaded', function () {
    var links = document.querySelectorAll('.logout-link');
    if (!links.length) return;

    var overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML =
        '<div class="modal">'
      + '  <h3 class="modal-title">Confirm Logout</h3>'
      + '  <div class="modal-body">Do you really want to logout?</div>'
      + '  <div class="modal-actions">'
      + '    <button type="button" class="btn-outline" id="logoutCancelBtn">Cancel</button>'
      + '    <button type="button" class="btn-danger" id="logoutConfirmBtn">Logout</button>'
      + '  </div>'
      + '</div>';
    document.body.appendChild(overlay);

    var targetHref = null;
    var cancelBtn = document.getElementById('logoutCancelBtn');
    var confirmBtn = document.getElementById('logoutConfirmBtn');

    links.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            targetHref = link.getAttribute('href');
            overlay.style.display = 'flex';
        });
    });

    cancelBtn.addEventListener('click', function () {
        overlay.style.display = 'none';
        targetHref = null;
    });

    confirmBtn.addEventListener('click', function () {
        if (targetHref) {
            window.location.href = targetHref;
        }
    });
});
