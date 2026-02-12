<?php

namespace Aksa\Http;

abstract class Controller
{
    protected function view($view, $data = [])
    {
        return Response::view($view, $data);
    }
    
    protected function json($data, $statusCode = 200)
    {
        return Response::json($data, $statusCode);
    }
    
    protected function redirect($url)
    {
        return Response::redirect($url);
    }
    
    protected function validate(Request $request, array $rules)
    {
        $errors = [];
        $data = $request->all();
        
        foreach ($rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            
            foreach ($rules as $rule) {
                if ($rule === 'required' && empty($data[$field])) {
                    $errors[$field][] = "The {$field} field is required.";
                }
                
                if (strpos($rule, 'min:') === 0 && isset($data[$field])) {
                    $min = (int) substr($rule, 4);
                    if (strlen($data[$field]) < $min) {
                        $errors[$field][] = "The {$field} must be at least {$min} characters.";
                    }
                }
                
                if (strpos($rule, 'max:') === 0 && isset($data[$field])) {
                    $max = (int) substr($rule, 4);
                    if (strlen($data[$field]) > $max) {
                        $errors[$field][] = "The {$field} may not be greater than {$max} characters.";
                    }
                }
                
                if ($rule === 'email' && isset($data[$field])) {
                    if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "The {$field} must be a valid email address.";
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            return $errors;
        }
        
        return true;
    }
}
