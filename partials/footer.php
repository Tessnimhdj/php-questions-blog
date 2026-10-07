    </div>
    <?php if (!empty($confirmDelete)): ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = document.getElementById('deleteModal');
                var confirmButton = document.getElementById('confirmDelete');
                var form = null;

                if (!modal || !confirmButton) {
                    return;
                }

                modal.addEventListener('show.bs.modal', function (event) {
                    var trigger = event.relatedTarget;
                    var formId = trigger ? trigger.getAttribute('data-form') : '';
                    var name = trigger ? trigger.getAttribute('data-name') : '';
                    form = formId ? document.getElementById(formId) : null;
                    var nameNode = modal.querySelector('.delete-question-name');
                    if (nameNode) {
                        nameNode.textContent = name || '';
                    }
                });

                confirmButton.addEventListener('click', function () {
                    if (form) {
                        form.submit();
                    }
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>

