<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//Create App object
$app = new \Slim\App;

//Get all Books
$app->post('/api/books', function(Request $request, Response $response){
    
    $data = $request->getParams();
    
    try {

        //create db object
        $db = new DB();
        //connect with db
        $db = $db->connect();

        $joins = "
            FROM 
                books_book a 
            LEFT JOIN 
                books_book_authors b on b.book_id = a.id 
            LEFT JOIN 
                books_author c on b.author_id = c.id
            LEFT JOIN
                books_book_languages d on d.book_id = a.id
            LEFT JOIN
                books_language e on e.id = d.language_id
            LEFT JOIN
                books_book_subjects f on f.book_id = a.id
            LEFT JOIN
                books_subject g on g.id = f.subject_id    
            LEFT JOIN
                books_book_bookshelves h on h.book_id = a.id
            LEFT JOIN
                books_bookshelf i on i.id = h.bookshelf_id
            LEFT JOIN
                books_format j on j.book_id = a.id";

        $countSql = "
            SELECT 
                count(a.id)".$joins;

        //Form the sql to get the data
        $dataSql = "
            SELECT  
                a.id,
                a.title as bookTitle,
                c.name as author_name,
                c.birth_year as author_birth,
                c.death_year as author_death,
                group_concat(distinct e.code SEPARATOR '!**!') as languages,
                group_concat(distinct g.name SEPARATOR '!**!') as subjects,
                group_concat(distinct i.name SEPARATOR '!**!') as bookshelves,
                group_concat(distinct concat(j.url, '!*!', j.mime_type) SEPARATOR '!**!') as links"
                .$joins;
        
        //Set initial for offset and limit of the query
        $offset = 0;
        $limit = 25;

        //Check if the filters are passed in POST
        //and if filters are passed then apply it on the data
        if(!empty($data)) {


            if (!empty($data['bookId'])) {
                $data['bookId'] = filter_var_array($data['bookId'], FILTER_SANITIZE_NUMBER_INT);
                $arrWhere[] = " a.id in (".implode(",", $data['bookId']).")";
            }

            if (!empty($data['language'])) {
                $arrWhere[] = " e.code in ('".implode("','", $data['language'])."')";
            }
            
            if (!empty($data['mimeType'])) {
                $arrWhere[] = " j.mime_type in ('".implode("','", $data['mimeType'])."')";
            }

            if (!empty($data['topic'])) {
                $arrWhere[] = " (g.name REGEXP ".$db->quote(implode("|", $data['topic']))."". " OR i.name REGEXP ".$db->quote(implode("|", $data['topic'])).")";
            }

            if (!empty($data['author'])) {
                $arrWhere[] = " c.name REGEXP ".$db->quote(implode("|", $data['author']));
            }

            if (!empty($data['title'])) {
                $arrWhere[] = " a.title REGEXP ".$db->quote(implode("|", $data['title']));
            }

            if (!empty($data['start'])) {
                $data['start'] = filter_var_array($data['start'], FILTER_SANITIZE_NUMBER_INT);
                $offset = $data['start'];
            }
        }

        $strWhere = count($arrWhere) > 1 ? implode(' AND ', $arrWhere) : $arrWhere[0];

        if (isset($strWhere) && $strWhere) {
            $countSql .= ' WHERE '.$strWhere;
            $dataSql .= ' WHERE '.$strWhere;
        }
        
        //fetch as per the popularity of the books        
        $countSql .= " GROUP BY a.id ORDER BY a.download_count DESC"; 
        $dataSql .= " GROUP BY a.id ORDER BY a.download_count DESC limit ".$offset .", ".$limit;  

        //Get number of the records
        $countStmt = $db->prepare($countSql);
        $countStmt->execute();
        $booksAvailable = $countStmt->rowCount();
        
        //Get data
        $dataStmt = $db->prepare($dataSql);
        $dataStmt->execute();
        $books = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        
        //No of records available
        $responseData = array("matchingBooks"=> $booksAvailable); 
        
        
        //Convert the data into the required format
        foreach($books as $val) {
            
            //Book object --> Title
            $tempData['bookInfo']['title'] = $val['bookTitle'];

            //Author information
            $tempData['bookInfo']['authorInfo']['name'] = $val['author_name'];
            $tempData['bookInfo']['authorInfo']['birthYear'] = $val['author_birth'];
            $tempData['bookInfo']['authorInfo']['deathYear'] = $val['author_death'];

            //Language Information
            $tempData['bookInfo']['languages'] = explode('!**!', $val['languages']);

            //Subject Information
            $tempData['bookInfo']['subjects'] = explode('!**!', $val['subjects']);

            //Bookshelf information
            $tempData['bookInfo']['bookshelves'] = explode('!**!', $val['bookshelves']);

            $arrTempLinks = array();
            $arrTempLinks = explode('!**!', $val['links']);
            $arrLinks = array();
        
            foreach ($arrTempLinks as $links) {
                list($temp['url'],  $temp['mimeType']) = explode('!*!',$links);
                $arrLinks[] = $temp;
            }
            
            //mime-type and links
            $tempData['bookInfo']['downloadLinks'] =  $arrLinks;

            $responseData['data'][] = $tempData;
        }
            


         return safe_json_encode($responseData);
         

    } catch(PDOException $e) {

        echo '{ "error" : {"text" : ' . $e->getMessage() . '}}';
    }

});


