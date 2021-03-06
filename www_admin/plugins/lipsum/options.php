<?
if (!defined("PLUGINOPTIONS")) exit();

function lipsum_string( $length = 10 )
{
  $words = array( // gargaj's collection of inherently funny words
    "Apple", "Ball", "Banana", "Beef", "Beer",
    "Bird", "Bread", "Bucket", "Burger", "Bus",
    "Car", "Cat", "Cheese", "Chicken", "Coat",
    "Cookie", "Dog", "Donut", "Door", "Duck",
    "Dwarf", "Fish", "Flower", "Giant", "Giraffe",
    "Hat", "Jam", "Lettuce", "Light", "Mail",
    "Moose", "Moon", "Nose", "Paper", "Pickle",
    "Pizza", "Potato", "Radio", "Sauce", "Shark",
    "Soup", "Space", "Stick", "Taco", "Tuba",
    "Tomato",
  );
  $a = array();
  $s = "";
  do
  {
    $a[] = $words[ array_rand($words) ];
    $s = implode(" ",$a);
  } while (strlen($s) < $length);
  return $s;
}

function lipsum_delete_all_entries()
{
  $entries = SQLLib::selectRows(sprintf_esc("select * from compoentries"));
  foreach($entries as $entry)
  {
    $dirname = get_compoentry_dir_path($entry);
    if (!$dirname) die("Error while getting compo entry dir");
    
    $a = glob($dirname."*");
    foreach ($a as $v)
      unlink($v);
    rmdir($dirname);
  }
  SQLLib::Query("truncate compoentries;");
}
function lipsum_delete_all_compos()
{
  $compos = SQLLib::selectRows(sprintf_esc("select * from compos"));
  foreach($compos as $compo)
  {
    $dirname = get_compo_dir($compo);

    rmdir($dirname);
  }
  SQLLib::Query("truncate compos;");
}
if ($_POST["truncate"])
{
  if ($_POST["truncate"]["compoentries"] == "on")
  {
    SQLLib::Query("truncate votes_range;");
    SQLLib::Query("truncate votes_preferential;");
    lipsum_delete_all_entries();
    printf("<div class='success'>Deleted all entries and votes</div>");
  }
  if ($_POST["truncate"]["compos"] == "on")
  {
    SQLLib::Query("truncate votes_range;");
    SQLLib::Query("truncate votes_preferential;");
    lipsum_delete_all_entries();
    lipsum_delete_all_compos();
    printf("<div class='success'>Deleted all compos, entries, and votes</div>");
  }
  if ($_POST["truncate"]["users"] == "on")
  {
    SQLLib::Query("update compoentries set userid = 0");
    SQLLib::Query("update votekeys set userid = 0");
    SQLLib::Query("truncate users;");
    printf("<div class='success'>Deleted all users</div>");
  }  
}
if ($_POST["fill"])
{
  if ($_POST["fill"]["users"] == "on")
  {
    for ($x = 0; $x < 5; $x++)
    {
      $name = str_replace(" ","",lipsum_string(10));
      SQLLib::InsertRow("users",array(
        "username" => $name,
        "nickname" => $name,
        "password" => hashPassword($name),
        "group" => lipsum_string(10),
      ));
    }
    printf("<div class='success'>Generated 30 new entries</div>");
  }
  if ($_POST["fill"]["compos"] == "on")
  {
    for ($x = 0; $x < 10; $x++)
    {
      $name = lipsum_string(15);
      SQLLib::InsertRow("compos",array(
        "name" => $name,
        "dirname" => str_replace(" ","_",strtolower($name)),
        "start" => date("Y-m-d H:i:s",time() + rand(60*60,5*60*60)),
      ));
    }
    printf("<div class='success'>Generated 10 new compos</div>");
  }
  if ($_POST["fill"]["compoentries"] == "on")
  {
    $compoids = array_map(function($i){ return $i->id; }, SQLLib::SelectRows("select id from compos") );
    $userids = array_map(function($i){ return $i->id; }, SQLLib::SelectRows("select id from users") );
    $userids[] = 0;
    for ($x = 0; $x < 30; $x++)
    {
      $output = array();
      $tmp = tempnam(ini_get('upload_tmp_dir'),"WUHULIPSUM_").".txt";
      file_put_contents($tmp,lipsum_string(10240));
      if (!handleUploadedRelease(array(
        "compoID" => $compoids[ array_rand($compoids) ],
        "userID" => $userids[ array_rand($userids) ],
        "title" => lipsum_string(32),
        "author" => lipsum_string(16),
        "comment" => lipsum_string(140),
        "localFileName" => $tmp,
        "originalFileName" => basename($tmp),
      ), $output))
      {
        printf("<div class='error'>".$output["error"]."</div>");
      }
      unlink($tmp);
    }
    printf("<div class='success'>Generated 30 new entries</div>");
  }
}

echo "<form method='post'>";
echo "<label>Select components to reset</label>";
echo "<ul>";
echo " <li><input type='checkbox' name='truncate[compos]' id='truncate-compos'/> <label for='truncate-compos'>Compos</label></li>";
echo " <li><input type='checkbox' name='truncate[compoentries]' id'=truncate-compoentries'/> <label for='truncate-compoentries'>Compo entries</label></li>";
echo " <li><input type='checkbox' name='truncate[users] id='truncate-users'/> <label for='truncate-users'>Users</label></li>";
echo "</ul>";
echo "<label>Select components to fill with lorem ipsum</label>";
echo "<ul>";
echo " <li><input type='checkbox' name='fill[compos]' id='fill-compos'/> <label for='fill-compos'>Compos</label> </li>";
echo " <li><input type='checkbox' name='fill[compoentries]' id='fill-compoentries'/> <label for='fill-compoentries'>Compo entries</label></li>";
echo " <li><input type='checkbox' name='fill[users]' id='fill-users'/> <label for='fill-users'>Users (Name and password will be the same!)</label></li>";
echo "</ul>";
echo "<input type='submit' value='Do'/>";
echo "</form>";


?>