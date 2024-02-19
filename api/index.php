<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT"); /*.$_SERVER['REQUEST_METHOD']); */
header("Content-Type: multipart/form-data;");

$get_boundary = strstr($_SERVER['CONTENT_TYPE'],'=-');
$get_boundary = strstr($get_boundary,'-');

$param = file_get_contents('php://input'); 
$param = str_replace($get_boundary,'',$param);
$param = str_replace('--','',$param);
$param = strstr($param,'"',false);
$param = str_replace('Content-Disposition: form-data; name=','',rtrim($param));
$param = trim(preg_replace('/\s\s+/', '*', rtrim($param).'"'));
$param = str_replace('"*','": "',$param);
$param = str_replace('*','" ',$param);
$param = str_replace('" "','", "',$param);
$param = "{".$param."}";
$param = json_decode($param);

switch($param->Method){
    case "HasRecord":
        $result = HasRecord($param->client_cd);
        break;
    case "GetBGICode":
        $result = GetBGICode($param->companyid_no);
        break;
    case "RegCompany":
        $result = RegCompany($param->productid_no, $param->client_cd, $param->client_nm, $param->tel_nb, $param->mobile_nb, $param->email_ad, $param->website_tx);
        break;
    case "ValidateKeyGen":
        $result = ValidateKeyGen($param->keygen_nb,$param->bgi_cd);
        break;
    case "ActivateKeyGen":
        $result = ActivateKeyGen($param->keyid_no, $param->hdd_nb, $param->macadd_nb, $param->activate_dt);
        break;
    case "HasRecordKeyGen":
        $result = HasRecordKeyGen($param->keygen_nb,$param->hdd_nb,$param->macadd_nb);
        break;
    default:
        echo "Invalid Method: ".$param->Method."!";
}

function HasRecord($client_cd) {
    include_once 'connection.php';
    $output = null;
    $query = "
                SELECT * FROM master_client
                WHERE client_cd = '$client_cd'
            ";
    $result = mysqli_query($connection, $query);
    if (mysqli_num_rows($result) == 0) {
        echo 0;
    } else {
        echo 1;
    }
}

function RegCompany($productid_no, $client_cd, $client_nm, $tel_nb, $mobile_nb, $email_ad, $website_tx) {
    include_once 'connection.php';
    $query = "
                INSERT INTO master_client (productid_no, client_cd, client_nm, tel_nb, mobile_nb, email_ad, website_tx)
                VALUES ('$productid_no', '$client_cd', '$client_nm', '$tel_nb', '$mobile_nb', '$email_ad', '$website_tx')
                
            ";
    if(mysqli_query($connection, $query)) {
        echo mysqli_insert_id($connection);
    } else {
        echo 0;
    }
}

function GetBGICode($companyid_no) {
    include_once 'connection.php';
    $output = null;
    $query = "
                SELECT * FROM master_client
                WHERE id_no = '$companyid_no'
            ";
    $result = mysqli_query($connection, $query);
    while($row = mysqli_fetch_array($result))
    {
        //$output[] = $row;
        $output["bgi_cd"] = $row["bgi_cd"];
     }
    echo json_encode($output);
}

function ValidateKeyGen($keygen_nb, $bgi_cd){
    include_once 'connection.php';
    $output = null;
    $query = "
                SELECT * FROM master_keygen
                WHERE keygen_nb = '$keygen_nb' AND bgi_cd = '$bgi_cd'
            ";
    $result = mysqli_query($connection, $query);
    while($row = mysqli_fetch_array($result))
    {
        //$output[] = $row;
        $output["id_no"] = $row["id_no"];
        $output["keygen_nb"] = $row["keygen_nb"];
        $output["bgi_cd"] = $row["bgi_cd"];
        $output["expiration_dt"] = $row["expiration_dt"];
        $output["limit_no"] = $row["limit_no"];
        $output["user_no"] = $row["user_no"];
        $output["keygen_ty"] = $row["keygen_ty"];
     }
    echo json_encode($output);
}

function ActivateKeyGen($keyid_no, $hdd_nb, $macadd_nb, $activate_dt){
    include_once 'connection.php';
    $result = 0;
    $query = "
                INSERT INTO keygen_users (keyid_no, hdd_nb, macadd_nb, activate_dt)
                VALUES ('$keyid_no', '$hdd_nb', '$macadd_nb', '$activate_dt')
                
            ";
    if(mysqli_query($connection, $query)) {
        $result = 1;
    } else {
        $result = 0;
    }
    
    if ($result == 1) {
        //Update users column
        $query = "
                    UPDATE master_keygen SET user_no = user_no + 1
                    WHERE id_no = '$keyid_no'
                ";
        if(mysqli_query($connection, $query)) {
            echo 1;
        } else {
            echo 0;
        }
    } else {
        echo 0;
    }
}

function HasRecordKeyGen($keygen_nb, $hdd_nb, $macadd_nb) {
    include_once 'connection.php';
    $output = null;
    $query = "
                SELECT * FROM view_keygen_user
                WHERE keygen_nb = '$keygen_nb'
                AND (hdd_nb = '$hdd_nb' OR macadd_nb = '$macadd_nb')
                LIMIT 1
            ";
    $result = mysqli_query($connection, $query);
    while($row = mysqli_fetch_array($result))
    {
        //$output[] = $row;
        $output["keyid_no"] = $row["keyid_no"];
        $output["keygen_nb"] = $row["keygen_nb"];
        $output["bgi_cd"] = $row["bgi_cd"];
        $output["hdd_nb"] = $row["hdd_nb"];
        $output["macadd_nb"] = $row["macadd_nb"];
        $output["expiration_dt"] = $row["expiration_dt"];
    }
    echo json_encode($output);
}

?>