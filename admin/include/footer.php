<div class="footer">
    <div class="copyright">
        <p style="white-space:nowrap;">Copyright &copy; 2026 Kapraa &mdash; Developed by <a href="https://www.weblypro.site" target="_blank" rel="noopener noreferrer">Weblypro</a></p>
    </div>
</div>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-body">
          <h4 class="text-center">Are You Sure You Want To Delete This ?</h4>
          <input type="hidden" name="table[]" id="modal_table" value="">
          <input type="hidden" name="column[]" id="modal_column" value="">
          <input type="hidden" name="value[]" id="modal_value" value="">
        </div>
        <div class="modal-footer">
		  <button type="submit" name="deleted" class="btn btn-danger btn-sm">Yes</button>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">No</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!--**********************************
	Scripts
***********************************-->
<!-- Required vendors -->
<?php if (!defined('GLOBAL_JS_LOADED')): ?>
<script src="vendor/global/global.min.js"></script>
<?php endif; ?>


<!-- <script src="vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script> -->

<!-- Apex Chart -->
<script src="vendor/apexchart/apexchart.js"></script>
<script src="vendor/chartjs/chart.bundle.min.js"></script>

<!-- Chart piety plugin files -->
<script src="vendor/peity/jquery.peity.min.js"></script>


<!-- Dashboard 1 -->
<script src="js/dashboard/dashboard-1.js"></script>

<script src="vendor/owl-carousel/owl.carousel.js"></script>

<script src="js/custom.min.js"></script>
<script src="js/dlabnav-init.js"></script>

<script>
	function JobickCarousel()
		{

			
			jQuery('.front-view-slider').owlCarousel({
				loop:false,
				margin:30,
				nav:true,
				autoplaySpeed: 3000,
				navSpeed: 3000,
				autoWidth:true,
				paginationSpeed: 3000,
				slideSpeed: 3000,
				smartSpeed: 3000,
				autoplay: false,
				animateOut: 'fadeOut',
				dots:true,
				navText: ['', ''],
				responsive:{
					0:{
						items:1,
						
						margin:10
					},
					
					480:{
						items:1
					},			
					
					767:{
						items:3
					},
					1750:{
						items:3
					}
				}
			})
		}

		jQuery(window).on('load',function(){
			setTimeout(function(){
				JobickCarousel();
			}, 1000); 
		});

</script>
<!-- Script to populate modal inputs -->
<script>
	document.addEventListener('DOMContentLoaded', function () {
		const deleteModal = document.getElementById('deleteModal');

		deleteModal.addEventListener('show.bs.modal', function (event) {
			const button = event.relatedTarget; // Button that triggered the modal
			const id = button.getAttribute('data-id');
			const table = button.getAttribute('data-table');
			const column = button.getAttribute('data-column');

			document.getElementById('modal_table').value = table;
			document.getElementById('modal_column').value = column;
			document.getElementById('modal_value').value = id;
		});
	});
</script>

<script>
/* ── Global preloader failsafe — fixes all admin pages ── */
(function(){
  function killLoader(){
    var p = document.getElementById('preloader');
    if(p){ p.style.cssText='display:none!important'; try{p.remove();}catch(e){} }
    var m = document.getElementById('main-wrapper');
    if(m) m.classList.add('show');
  }
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', function(){ setTimeout(killLoader, 200); });
  } else {
    setTimeout(killLoader, 200);
  }
  setTimeout(killLoader, 2500);
})();
</script>
</body>
</html>