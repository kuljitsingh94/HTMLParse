<?php

#give the script the URL to scrape
$URLbegin= "https://cs.csubak.edu/~clei/teaching/files/S19_3500/hw";
$URLend = "s.html";

#function that returns an array populated with everything
#in between the parameters tag_open and tag_close
function tag_contents($string, $tag_open, $tag_close){ 
   foreach (explode($tag_open, $string) as $key => $value) {
       if(strpos($value, $tag_close) !== FALSE){
            $result[] = trim(substr($value, 0, strpos($value, $tag_close)));
       }
   }
   return $result;
}

$output = fopen('./output.txt','w');

for($hwNum = 1; $hwNum <= 5; $hwNum++) {
    if($hwNum < 10) 
        $URL = $URLbegin."0".$hwNum.$URLend;
    else
        $URL = $URLbegin.$hwNum.$URLend;
#download HTML from URL and save as a string
    $file = file_get_contents($URL);

#get the number of questions from the num_quests variable
#in the HTML/JavaScript
    $NUM_QUESTIONS = tag_contents($file, "num_quests =",";");

#save as an integer since tag_contents returns strings
#also look at subscript 1 since subscript 0 is garbage
    $NUM_QUESTIONS = trim($NUM_QUESTIONS[1])+0;

#remove all head tag info, leaving just the body
#which is all we want
    $body = substr($file,strpos($file, "<pre class=verbatim>"));
#get all comparison expressions since this is where
#the answers are located
    $parsed= tag_contents($body, 'if (', ')');
    $questions= tag_contents($body, '<pre class=verbatim>', '</pre>');
    array_unshift($questions, "placeholder");
    //var_dump($questions);
    $answers = array();
    $i = 0;

#all string parsing below requires knowledge of the
#structure of the
#downloaded HTML and requires pattern recognition

#traverse array full of comparison expressions
    foreach($parsed as $key){

        #break the comparison expressions into separate
        #variables, delimited by &&
        $ifStatment = explode("&&",$key);

        #this checks if the if statement we grabbed
        #pertains to answers or something else
        if(!strpos($key,"hwkform")) {
            continue;
        }

        #if previous if statement fails, we have an
        #answer if statement
        $i++;

        #traverse each comparison expression
        foreach($ifStatment as $value){
            
            #remove whitespace
            $value = trim($value);
            
            #! tells us its a wrong answer, so 
            #if the ! is not there, meaning its a
            #right answer
            if($value[0]!='!'){

                #grab the value of right answers,0123
                $answer = tag_contents($value, '[',']');
                
                #output formatting, convert 0123 -> ABCD
                #by ascii value usage
                if(isset($answers[$i]))
                    $answers[$i].=','.chr($answer[0]+65);
                else
                    $answers[$i]=chr($answer[0]+65);
            }
        }
    }

#error checking, if NUM_QUESTIONS != i then we
#got an extra if statement comparison expression
    if($i!=$NUM_QUESTIONS) {
        echo "\n\n\tERROR PARSING HTML! DOUBLE CHECK ANSWERS!!\n\n\n";
    }

    echo "\tGenerating Study Guide Output File for $hwNum\n";
#output questions and answers
    
    
    //var_dump($answers);
#write to output file
    for($i = 1; $i<=$NUM_QUESTIONS ; $i++) {
        fwrite($output, $questions[$i]."<split>".$answers[$i].'<endofquestion>');
    }

    foreach($answers as $question => $ans) {
        
        //usleep(0250000);
        echo "Question $question\t: $ans\n";
    }
}
?>
