<?php
class ClassHelper
{
  
    public function getDurationFunc($duration, $category, $uni_id)
    {

    //       echo "<pre>DEBUG: duration = $duration, category = ";
    // print_r($category);
    // echo ", uni_id = $uni_id</pre>";die;
        if ($uni_id == 48) {
            if (strtolower($category) == 'certified' && ($duration == 6 || $duration == 11)) {
                $duration = $duration . '/' . $category;
            } elseif (strtolower($category) == 'certification') {
                $duration = $duration . '/certification';
            } elseif (strtolower($category) == 'advance_diploma' || $duration == '11/advance-diploma') {
                $duration = '11/advanced';
            } elseif (strtolower($category) == 'post_graduate') {
                $duration = '24/post-graduate';
            }
        }
        return $duration;
    }
    
        public function getUserSubCourse($conn, $added_for, $role,$uni_id)
    {
        if($role === "Center" || $role === "Sub-Center" ) {
            $userSQL = "SELECT Sub_Course_ID AS ID, Sub_Courses.Name, Sub_Courses.Short_Name FROM Sub_Courses left join Students ON Sub_Courses.ID =Students.Sub_Course_ID  WHERE `Added_By` = $added_for  GROUP BY Sub_Course_ID";
        }else{
            $userSQL = "SELECT ID, Name,Short_Name  FROM Sub_Courses WHERE Status =1 AND University_ID = " . $uni_id . " order by Name ASC";
        }
        $option = "";
        $userSqlQuery = $conn->query($userSQL);
        if ($userSqlQuery->num_rows == 0) {
            $option = "<option>Sub-Course not assign to this user</option>";
        } else {
            while ($row = $userSqlQuery->fetch_assoc()) {
                $option .= "<option value=" . $row['ID'] . ">" . $row['Name'].'('.$row['Short_Name']. ")</option>";
            }
        }
        return $option;
    }
}

