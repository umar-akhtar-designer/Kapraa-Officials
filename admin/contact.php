<?php
	require 'include/head.php';
	session('WEBSITE-LOGIN');	
?>
<div class="content-body">
	<div class="container-fluid">
		<div class="col-lg-12">
			<div class="card">
				<div class="card-header">
					<h4 class="card-title">Contact Us</h4>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-bordered table-responsive-sm">
							<thead>
								<tr>
									<th>Name</th>
									<th>Email</th>
									<th>Phone</th>
									<th>Subject</th>
									<th>Message</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php

									$contactQuery = selectQuery("SELECT * FROM contact order by id desc",

									[
                                        'dataPerPage'		=>	'20',
                                        'searchVariable'	=>	'search=' . $searchText . '&',
                                        'getVariable'		=>	'page',
                                        'PageUrl'			=>	'contact.php',
                                    ]);

									if($contactQuery['status'] == true){
										foreach($contactQuery['data'] as $sr => $data){
											$contactld = $data['id'];
											$name = $data['name'];
											$email = $data['email'];
											$phone = $data['phone'];
											$subject = $data['subject'];
											$message = $data['message'];
																			
										echo '<tr>
												<th>'.$name.'</th>
												<td>'.$email.'</td>
												<td>'.$phone.'</td>
												<td>'.$subject.'</td>
												<td>'.$message.'</td>
												<td class="d-flex">
													<button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" 
														data-id="'.$contactld.'" data-table="contact" data-column="id">
														<i class="fa fa-trash"></i>
													</button>
												</td>
											</tr>';
										}                
									}
								?>
							</tbody>
						</table>
					</div>
					<div class="row py-3">
                        <div class="col-md-12 text-right">
                            <?php
                                echo $contactQuery['pagination'];
                            ?>
                        </div>
                    </div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
  include 'include/footer.php';
?>