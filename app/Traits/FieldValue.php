<?
namespace App\Traits;

use App\DataRow;

trait FieldValue
{
	public function getValue($field) {
		$value = '';
		switch ($field->type) {
			case 'file':
				$value = array();
				$files = json_decode($this->{$field->field},true);
                if(is_array($files)) {
                	if(count($files))
                    	$files = \App\Models\File::whereIn('id', $files)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$files).")"))->get();
                    else
                    	$files = \App\Models\File::whereIn('id', $files)->get();
                    foreach ($files as $file) {
                    	if($field->show_file_image)
                    		$value[] = $file;//->path;
                    	else
                    		$value[] = $file;//$file->name;
                    }
                }
                //$value = implode(', ', $value);
				break;

			case 'image':
				$value = array();
				$files = json_decode($this->{$field->field},true);
                if(is_array($files)) {
                	if(count($files))
                    	$files = \App\Models\File::whereIn('id', $files)->orderByRaw(\DB::raw("FIELD(id, ".implode(",",$files).")"))->get();
                    else
                    	$files = \App\Models\File::whereIn('id', $files)->get();
                    foreach ($files as $file) {
                    	if($field->show_file_image)
                    		$value[] = $file;//->path;
                    	else
                    		$value[] = $file;//$file->name;
                    }
                }
                //$value = implode(', ', $value);
				break;
			
			case 'select_dropdown':
				if($field->is_plural) {
					if($this->{$field->field}) {
						$vals = json_decode($this->{$field->field}, true);
						$data_field = \DB::table('data_rows')->where(['id' => $field->id])->first();
						if($details = json_decode($data_field->details, true)) {
							$values = array();
							if(isset($details['options'])) {
								foreach($details['options'] as $id => $option) {
									if(is_array($vals) && in_array($id, $vals))
										$values[] = $option;
								}
								if(count($values) > 0 && $values[0])
									$value = implode(', ', $values);
							} elseif(isset($details['table'])) {
								$vals = json_decode($this->{$field->field}, true);
								$options = \DB::table($details['table'])->get();
								$table_values = array();
								foreach($options as $option) {
									if(is_array($vals) && in_array($option->id, $vals))
										$values[] = isset($option->display_name) && $option->display_name ? $option->display_name : $option->name;
								}
								if(count($values) > 0 && $values[0])
									$value = implode(', ', $values);
							} else {
								$value = $this->{$field->field};
							}
						}
					} else {
						$value = $this->{$field->field};
					}
				} else {
					$data_field = \DB::table('data_rows')->where(['id' => $field->id])->first();
					if($details = json_decode($data_field->details, true)) {
						if(isset($details['options'][$this->{$field->field}])) {
							$value = $details['options'][$this->{$field->field}];
						} elseif(isset($details['table'])) {
							//$value = $this->{$field->field};
							if($details['table'] == 'users')
								$options = \DB::table($details['table'])->whereNull('deleted_at')->get();
							else
								$options = \DB::table($details['table'])->get();
							foreach($options as $option) {
								if($option->id == $this->{$field->field})
									$value = (isset($option->display_name) ? $option->display_name : $option->name).(isset($option->last_name) ? ' '.$option->last_name : '');
							}
						} else {
							$value = $this->{$field->field};
						}
					}
				}
				break;

			case 'multiple_checkbox':
				if($this->{$field->field}) {
					$vals = json_decode($this->{$field->field}, true);
					$data_field = \DB::table('data_rows')->where(['id' => $field->id])->first();
					if($details = json_decode($data_field->details, true)) {
						$values = array();
						if(isset($details['options'])) {
							foreach($details['options'] as $id => $option) {
								if(is_array($vals) && in_array($id, $vals))
									$values[] = $option;
							}
							if(count($values) > 0 && $values[0])
								$value = implode(', ', $values);
						} elseif(isset($details['table'])) {
							$vals = json_decode($this->{$field->field}, true);
							$options = \DB::table($details['table'])->get();
							$table_values = array();
							foreach($options as $option) {
								if(is_array($vals) && in_array($option->id, $vals))
									$values[] = $option->display_name ? $option->display_name : $option->name;
							}
							if(count($values) > 0 && $values[0])
								$value = implode(', ', $values);
						} else {
							$value = $this->{$field->field};
						}
					}
				} else {
					$value = $this->{$field->field};
				}
				
				break;

			default:
				if(preg_match('/(http|ftp|mailto)/', $this->{$field->field}, $matches))
					$value = '<a href="'.$this->{$field->field}.'" target="_blank">'.$this->{$field->field}.'</a>';
				else
					$value = $this->{$field->field};
				break;
		}
		return $value && $value != 'null' ? $value : '<span class="empty-val">не заполнено</span>';
	}

	public static function getFieldByCode(string $model, string $code) {
		$row_type = \DB::table('data_types')->where('name', $model)->first();
		$field = \DB::table('data_rows')->where(['data_type_id' => $row_type->id, 'field' => $code])->first();

		return $field;
	}
}