<?php
    $teamQuery = selectQuery("SELECT * FROM team");
    if($teamQuery['status'] == true){
        foreach($teamQuery['data'] as $sr => $data){
            $name			= $data['name'];
            $profession	    = $data['profession'];
            $image			= $data['image'];
            
            echo '
                <div class="team-item col-6 col-sm-6 col-md-4 col-lg-4 text-center mb-4">
                    <div class="team-img">
                        <img class="blur-up lazyload w-100" src="admin/images/team/'.$image.'" alt="'.$name.'" />
                        <div class="social-url d-flex flex-wrap justify-content-center">
                            <a href="https://www.facebook.com/kapraaofficial" data-bs-toggle="tooltip" data-bs-placement="right" title="Facebook"><i class="an an-facebook-f"></i></a>
                            <a href="#" data-bs-toggle="tooltip" data-bs-placement="right" title="Linkedin"><i class="an an-linkedin"></i></a>
                        </div>
                    </div>
                    <div class="team-bio pt-2">
                        <p class="m-0"><strong>'.$name.'</strong></p>
                        <p>'.$profession.'</p>
                    </div>
                </div>
                ';	
            }
    }
?>

       
       