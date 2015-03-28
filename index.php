<?php

    // Configuration values
    $domain = "DOMAIN";
    $badExtensions = array("2013", "2014", "2015", "2016");

    // Get Keywords
    $keywords = $_GET["keywords"];

    // Blank Arrays
    $users = [];
    $lockedFiles = [];

    // If not blank
    if($keywords) {

        // Get a file into an array.
        echo shell_exec("smbstatus -S > /volume1/web/samba/users.txt");

        $sambaUsers = file('users.txt');

        // Loop through our array, show HTML source as HTML source; and line numbers too.
        foreach ($sambaUsers as $line_num => $line) {

            // Skip Blanks
            if ($line != "\n") {
                // Split the line by any number of space characters
                $mySplintLine = preg_split("/[\s]+/", $line);

                // Associate Values
                // pid  uid user pc group
                $pid = $mySplintLine[0];
                $user = $mySplintLine[2];

                // Create Array
                $users[$pid] = $user;
            }

        }

        // Get a file into an array.
        $sambaFiles = file('smbstatus.txt');

        // Loop through our array, skip the first 3 header lines
        $skip = 2;
        foreach ($sambaFiles as $line_num => $line) {
            // Skip Blanks
            if ($line != "\n" && $line_num > $skip) {
                // Split the line by any number of space characters
                $mySplintLine = preg_split("/[\s]+/", $line, 7);
                $myLastSplitLine = preg_split("/[\s]+/", $mySplintLine[6]);
                $countBlanks = count($myLastSplitLine);

                // Pid Uid DenyMode Access R/W Oplock SharePath Name Time
                $pid = $mySplintLine[0];
                $user = $users[$pid];

                // Assign Values
                $path = $myLastSplitLine[0];
                $file = implode(' ', array_slice($myLastSplitLine, 1, $countBlanks - 7));
                $time = implode(' ', array_slice($myLastSplitLine, $countBlanks - 6, $countBlanks));

                // Check Extension
                $fileName = pathinfo($file, PATHINFO_FILENAME);
                $ext = pathinfo($file, PATHINFO_EXTENSION);

                // Create Array if string matches file name or path
                if (strpos(strtolower($file), strtolower($keywords)) || strpos($file, $path) || $keywords == "all") {

                    // Check Extension and not temp file and not a bad extension
                    if ($ext && substr($fileName, 0, 1) !== '~' && !in_array($ext, $badExtensions)) {
                        $lockedFiles[$user][] = array($path, $file, $fileName, $time, $ext);
                    }
                }

            }
        }

    }
?>
<!DOCTYPE html>
<html>
  <head>
    <title><? echo "$keywords (".count($lockedFiles).")"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="css/fileicon.css" rel="stylesheet" media="screen">
      <style>
          #search, #results{
              margin-top:30px;
          }

          .user {
              width: 60px;
          }
          .img-circle{
              max-height: 50px;
          }
          .file-icon{
              float:left;
              margin:10px;
          }
      </style>
  </head>
  <body>

  <div class="container">
      <div class="row" id="search">
            <form action="index.php">
            <div class="col-md-6">
                <div id="custom-search-input">
                    <div class="input-group col-md-12">
                        <input type="text" name="keywords" class="form-control input-lg" placeholder="Search" value="<?= $keywords; ?>" />
                        <span class="input-group-btn">
                            <button class="btn btn-info btn-lg" type="button">
                                <i class="glyphicon glyphicon-search"></i>
                            </button>
                        </span>
                    </div>
                </div>
            </div>
            </form>
    </div>
    <div class="row">
        <div class="col-md-6">
            <table class="table table-bordered table-striped" id="results">
                <thead>
                    <tr>
                        <td class="user">User</td>
                        <td>Files</td>
                    </tr>
                </thead>
                <tbody>
	<?php 
	
	foreach($lockedFiles as  $user => $results){

        $username = str_replace("$domain\\",'', $user);

		echo "<tr>\n";
        if(file_exists('img/users/'.$username . '.jpg')){
            echo "	<td><img src='img/users/".$username.".jpg' class='img-circle' alt=\"$user\"></td>\n";
        }else{
            echo "	<td><img src='img/users/portrait.jpg' class='img-circle'  alt=\"$user\"></td>\n";
        }


		echo "	<td>\n";
		foreach ($results as $lockedFile){


                $fileLoc = str_replace("/volume1/","file://",$lockedFile[0]) ."/" . $lockedFile[1];
				echo "<a href=\"#\" data-toggle=\"tooltip\" title=\"\" data-original-title=\"$lockedFile[2].$lockedFile[4]\" data-path=\"$fileLoc\" title=\"$lockedFile[2]\" class=\"file-icon\" data-type=\"$lockedFile[4]\"></a>\n";

		}
		echo "	</td>\n";
		echo "</tr>\n\n";
	}
	
	?>
                    </tbody>
                </table>
            </div>
        </div>
	</div>
 
		<!-- Placed at the end of the document so the pages load faster -->
		<script src="js/jquery-2.1.3.min.js"></script>
		<script src="js/bootstrap.min.js"></script>
		<script>
			$(".file-icon").tooltip()
		</script>
		

</html>