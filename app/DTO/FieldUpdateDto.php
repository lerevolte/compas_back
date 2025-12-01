<?
namespace App\DTO;

final class FieldUpdateDto
{
    
    
    public function __construct(
        public readonly ?string $title,
        public readonly ?int $set_color,
        public readonly ?string $color,
        public readonly ?string $unit,
        public readonly ?string $button_name,
        public readonly ?int $section_id,
        public readonly ?int $visible_always,
        public readonly ?int $is_external_link,
        public readonly ?int $is_plural,
        public readonly ?int $is_hidden,
        public readonly ?int $required,
        public readonly ?int $show_file_name,
        public readonly array $options,
        public readonly ?int $has_roles_read,
        public readonly ?int $has_roles_write,
        public readonly array $roles_read,
        public readonly ?array $subfields,
        public readonly array $roles_write,
        public readonly ?int $change_section,
        public readonly ?int $sort
    ) 
    {
    }
    
}

