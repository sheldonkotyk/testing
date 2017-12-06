<?php
    
    //If the admin is using this, show the required fields, if it's a donor only show optional fields
    // if(Session::get('client_id'))
        define('required_badge', '<span class="label label-primary required pull-right">Required</span>');
    // else
        // define('required_badge', '');
    
    define('optional_badge', '<span class="label label-success required pull-right">Optional</span>');
    /**
     * Macros for creating form fields.
     * Used mainly for creating form fields from the fields table
     *
     * @data - array with row from field table
     */
    

    // Form::macro('hysText', function($data, $value = null) {
    // 	$placeholder = '';
    // 	if (!empty($data->field_data)) {
    // 		$placeholder = 'placeholder="'.$data->field_data.'"';
    // 	}
        
    // 	$required = optional_badge; $r = '';
    // 	if ($data->required == 1) {
    // 		$required = '<span class="label label-primary required">Required</span>';
    // 		$r = 'required';
    // 	}
        
    //     $out = '<div class="form-group">
    //     	<label for="'.$data->field_key.'">'.$data->field_label.' '.$required.'</label>
    //         <input type="text" class="form-control" id="'.$data->field_key.'" name="'.$data->field_key.'" value="'.$value.'" '.$placeholder.' '.$r.'>
    //     </div>';
        
    //     return $out;
    // });


    //I modified this macro so that the browser would remember the previous post input values
    Form::macro('hysText', function ($data, $value = null) {
        $placeholder = '';
        if (!empty($data->field_data)) {
            $placeholder = $data->field_data;
        }
        
        $required = optional_badge;
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }
        
        $out = '<div class="form-group">'.
            Form::label($data->field_key, $data->field_label).' '.$required.
            Form::text($data->field_key, $value, $attributes = ['placeholder' => $placeholder, 'class' => 'form-control', $r=>""]).
            '</div>';

        
        return $out;
    });
    
    Form::macro('hysTextarea', function ($data, $value = null) {
        $placeholder = '';
        if (!empty($data->field_data)) {
            $placeholder = 'placeholder="'.$data->field_data.'"';
        }
        
        $required = '<span class="label label-success required pull-right">Optional</span>';
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }

        $out = '<div class="form-group">
	    	<label for="'.$data->field_key.'">'.$data->field_label.' </label> '.$required.'
	    	<textarea class="form-control hysTextarea" id="'.$data->field_key.'" name="'.$data->field_key.'" '.$placeholder.' '.$r.'>'.$value.'</textarea>
	    </div>';
        
        return $out;
    });
    
    Form::macro('hysStatic', function ($data) {
        $out = '<div class="form-group">
			<hr>
			<h3>'.$data->field_label.'</h3>
			<p class="lead">'.$data->field_data.'</p>
			<hr>
		</div>';
        
        return $out;
    });
    
    Form::macro('hysDate', function ($data, $value = null) {
        $placeholder = 'placeholder="Enter date format YYYY-MM-DD"';
        if (!empty($data->field_data)) {
            $placeholder = 'placeholder="'.$data->field_data.'"';
        }
        
        if ($value != null) {
            $value = Carbon::createFromTimeStamp(strtotime($value))->toDateString();
        }

        $required = '<span class="label label-success required pull-right">Optional</span>';
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }
        
        $out = '<div class="form-group">
	    	<label for="'.$data->field_key.'">'.$data->field_label.' </label> '.$required.'
	        <input type="text" class="form-control datepicker" id="'.$data->field_key.'" name="'.$data->field_key.'" value="'.$value.'" '.$placeholder.' '.$r.'>
	    </div>';
        
        return $out;
    });

    //Age Macro
    Form::macro('hysAge', function ($data, $value = null) {
        $placeholder = 'placeholder="Enter date format YYYY-MM-DD"';
        if (!empty($data->field_data)) {
            $placeholder = 'placeholder="'.$data->field_data.'"';
        }
        
        if ($value != null&&$value!='0') {
            $value = Carbon::createFromTimeStamp(strtotime($value))->toDateString();
            //$age= Carbon::createFromTimeStamp(strtotime($value))->age;
        } else {
            $value = '';
        }

        $required = '<span class="label label-success required pull-right">Optional</span>';
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }
        
        $out = '<div class="form-group">
	    	<label for="'.$data->field_key.'">'.$data->field_label.' </label>'.$required.'
	        <input type="text" class="form-control datepicker" id="'.$data->field_key.'" name="'.$data->field_key.'" value="'.$value.'" '.$placeholder.' '.$r.'>
	    </div>';
        
        return $out;
    });
    
    Form::macro('hysLink', function ($data, $value = null) {
        $required = '<span class="label label-success required pull-right">Optional</span>';
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }
        
        $value0 = '';
        $value1 = '';
        if ($value != null) {
            $value = explode('|', $value);
            
            $value0 = 'value="'.$value[0].'"';
            $value1 = 'value="'.$value[1].'"';
        }
        
        
        $out = '<div class="form-group">
	    	<label for="'.$data->field_key.'">'.$data->field_label.' </label>'.$required.'
	    	<div class="row">
	    		<div class="col-lg-4">
					<input type="text" class="form-control" id="'.$data->field_key.'" name="'.$data->field_key.'[]" '.$value0.' placeholder="Enter link text" '.$r.'>
	    		</div>
	    		<div class="col-lg-4">
					<input type="url" class="form-control" id="'.$data->field_key.'" name="'.$data->field_key.'[]" '.$value1.' placeholder="Enter URL" '.$r.'>
	    		</div>
	        </div>
	    </div>';
        
        return $out;
    });
    
    Form::macro('hysSelect', function ($data, $value = null) {
        $required = optional_badge;
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }

        $items = explode(',', $data->field_data);
        $list = '';
        foreach ($items as $item) {
            $item = trim($item);
            $value = trim($value);
            $selected = '';
            if ($item == $value) {
                $selected = 'selected="selected"';
            }
            $list .= '<option value="'.$item.'" '.$selected.'>'.$item.'</option>';
        }
        
        $out = '<div class="form-group">
	    	<label for="'.$data->field_key.'">'.$data->field_label.'</label>'.$required.'
				<select name="'.$data->field_key.'" id="'.$data->field_key.'" class="form-control" '.$r.'>
					'.$list.'
				</select>	    
			</div>';
        
        return $out;
    });
    
    Form::macro('hysCheckbox', function ($data, $value = null) {
        
        $required = optional_badge;
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }

        $items = explode(',', $data->field_data);
        
        if ($value != null) {
            $values = json_decode($value, true);
        }
        
        $i = 0;
        $out = '<div class="form-group"><label>'.$data->field_label.' </label>'.$required.'<br>';
        $out .= '<input type="hidden" name="' . $data->field_key . '" value="">';
        foreach ($items as $item) {
            $checked = '';
            if (isset($values[$i])) {
                $checked = 'checked="checked"';
            }
            $out .= '<label class="checkbox-inline">
					<input type="checkbox" id="'.$data->field_key.''.$i.'" name="'.$data->field_key.'['.$i.']" value="checkbox" '.$checked.'> '. trim($item) .'
					</label>';
            $i++;
        }
        $out .= "</div>";
        
        return $out;
    });
    
    Form::macro('hysTable', function ($data, $value = null) {
        $required = optional_badge;
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }

        // return var_dump($value);
        
        $items = explode(',', $data->field_data);
        $out = '<div class="form-group">';
        $out .= '<label>'.$data->field_label.'</label>'.$required;
        $out .= '<table class="table table-condensed"><thead><tr>';
        
        foreach ($items as $item) {
            $out .= '<th>'.$item.'</th>';
        }
        
        $out .= '</tr></thead><tbody>';
        $count = count($items);
        
        if ($value == null) {
            $i = 0;
            $out .= '<tr class="'.$data->field_key.'">';
            while ($i < $count) {
                $out .= '<td><input type="text" id="'.$data->field_key.''.$i.'" name="'.$data->field_key.'[]" '.$r.'></td>';
                $i++;
            }
            $out .= '</tr>';
        } else {
            $table_data = json_decode($value, true);
            $i = 0;

            if (is_array($table_data)) {
                $td_quantity = count($table_data);
                $loops = $td_quantity/$count;
                $ii = 1;
                
                foreach ($table_data as $td) {
                    $i++;
                    
                    if ($ii == $loops) {
                        $class = ' class="'.$data->field_key.'"';
                    } else {
                        $class = '';
                    }
                    
                    if ($i == 1) {
                        $out .= '<tr'.$class.'>';
                        $ii++;
                    }
                    
                    $out .= '<td><input type="text" id="'.$data->field_key.''.$i.'" name="'.$data->field_key.'[]" value="'.$td.'" '.$r.'></td>';
                    
                    if ($i == $count) {
                        $i = 0;
                        $out .= '</tr>';
                    }
                }
            } else {
                $i = 0;
                $out .= '<tr class="'.$data->field_key.'">';
                while ($i < $count) {
                    $out .= '<td><input type="text" id="'.$data->field_key.''.$i.'" name="'.$data->field_key.'[]" '.$r.'></td>';
                    $i++;
                }
                $out .= '</tr>';
            }
        }
        
        $out .= '</tbody></table>';
        $out .= '<div class="add_row'.$data->field_key.'"><span class="glyphicon glyphicon-plus"></span> Add Row</div>';
        $out .= '</div>';
        $out .= '<script type="text/javascript">
				$(document).ready(function(){
					$(".add_row'.$data->field_key.'").css("cursor", "pointer");
					$(".add_row'.$data->field_key.'").on("click", function() {
						$("tr.'.$data->field_key.'").clone().insertAfter("tr.'.$data->field_key.'");
						$("tr.'.$data->field_key.':first").removeClass("'.$data->field_key.'");
						$("tr.'.$data->field_key.':last").find(":text").val(" ");
					});
				});
				</script>';

        return $out;
    });

    Form::macro('hysCustomid', function ($data, $value = null) {
        $permissions = Session::get('permissions');
        $placeholder = '';
        if (!empty($data->field_data)) {
            $placeholder = 'placeholder="'.$data->field_data.'"';
        }
        
        $required = optional_badge;
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }
        
        if (isset($permissions->group_all) && $permissions->group_all == 1) {
            $out = '<div class="form-group">
		    	<label for="'.$data->field_key.'">'.$data->field_label.'</label>'.$required.'
		        <input type="text" class="form-control" name="'.$data->field_key.'" value="'.$value.'" '.$placeholder.' '.$r.'>
		    </div>';
        } else {
            $out = '<div class="form-group">
		    	<label for="'.$data->field_key.'">'.$data->field_label.'</label>'.$required.'
		        <input type="text" class="form-control" value="'.$value.'" '.$placeholder.' '.$r.' disabled>
		        <input type="hidden" id="'.$data->field_key.'" name="'.$data->field_key.'" value="'.$value.'">
		    </div>';
        }
        
        return $out;
    });

//I modified this macro so that the browser would remember the previous post input values
    Form::macro('hysGatewayAddress', function ($data, $value = null) {
        $placeholder = '';
        if (!empty($data->field_data)) {
            $placeholder = $data->field_data;
        }
        
        $required = optional_badge;
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }
        
        $out = '<div class="form-group">'.
            Form::label($data->field_key, $data->field_label).' '.$required.
            Form::text($data->field_key, $value, $attributes = ['placeholder' => $placeholder, 'class' => 'form-control', $r=>""]).
            '</div>';

        
        return $out;
    });

//I modified this macro so that the browser would remember the previous post input values
    Form::macro('hysGatewayCity', function ($data, $value = null) {
        $placeholder = '';
        if (!empty($data->field_data)) {
            $placeholder = $data->field_data;
        }
        
        $required = optional_badge;
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }
        
        $out = '<div class="form-group">'.
            Form::label($data->field_key, $data->field_label).' '.$required.
            Form::text($data->field_key, $value, $attributes = ['placeholder' => $placeholder, 'class' => 'form-control', $r=>""]).
            '</div>';

        
        return $out;
    });

//I modified this macro so that the browser would remember the previous post input values
    Form::macro('hysGatewayState', function ($data, $value = null) {
        $placeholder = '';
        if (!empty($data->field_data)) {
            $placeholder = $data->field_data;
        }
        
        $required = optional_badge;
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }
        
        $out = '<div class="form-group">'.
            Form::label($data->field_key, $data->field_label).' '.$required.
            Form::text($data->field_key, $value, $attributes = ['placeholder' => $placeholder, 'class' => 'form-control', $r=>""]).
            '</div>';

        
        return $out;
    });

//I modified this macro so that the browser would remember the previous post input values
    Form::macro('hysGatewayZipCode', function ($data, $value = null) {
        $placeholder = '';
        if (!empty($data->field_data)) {
            $placeholder = $data->field_data;
        }
        
        $required = optional_badge;
        $r = '';
        if ($data->required == 1) {
            $required = required_badge;
            $r = 'required';
        }
        
        $out = '<div class="form-group">'.
            Form::label($data->field_key, $data->field_label).' '.$required.
            Form::text($data->field_key, $value, $attributes = ['placeholder' => $placeholder, 'class' => 'form-control', $r=>""]).
            '</div>';

        
        return $out;
    });
