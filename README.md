# Laravel Text Flags

_A Laravel package to replace flags found in the text with array values ​​or objects_


### Install 🔧

```bash
    composer require mmo-and-friends/laravel-text-flags
```

### Quick Sample Usage ⌨️

```php

use MmoAndFriends\LaravelTextFlags\TextFlags;

class HomeController extends Controller
{

    //
    // For capture the flags you need use the syntax: "{model:attribute}" or "{model:attribute.relation.attribute}"
    //


    /**
     * Maybe you need returns a partial html
     * 
     * @return \Illuminate\Response
     */
    public function contactInfo(){
        
        // Filling the text flags with the values 

        $textFlags = TextFlags::fill([
            'contact_info' => [
                'organization'  => 'Mmo&Friends',
                'manager_name'  => 'Guillermo Rodriguez'
                'manager_email' => 'guillermo.rod.dev@gmail.com',
            ],
            'items' => [
                [
                    'name' => 'Some book',
                    'price' => 450, 
                    'category' => [
                        'name' => 'Books'
                    ]
                ],
                [
                    'name' => 'Other book',
                    'price' => 340, 
                    'category' => [
                        'name' => 'Books'
                    ]
                ]
            ]
        ]);

        // Reading the text

        $html = $textFlags->read('        
            <div class="card contact-info">
                <p>Organization: {contact_info:organization}</p>
                <p>Manager: {contact_info:manager_name}</p>
                <p>Manager Email: {contact_info:manager_email}</p>
            </div>
            <ol>
                {each:items}
                    <li>{each_v:name} | ${each_v:price} | {each_v:category.name}</li>
                {end_each:items}
            </ol>
        ')
        ->apply();

        return $html;
    }

   
    /**
     * In some cases you need that the users uploads an custom format for a ticket, pdf, etc,,,
     * 
     * But use the blade engine can be dangerous for sql injections or query statements with the @php directive,
     * so that was the reason for i create this package
     * 
     * In this way you can return the custom user format (html) with the real value to the pdf api or do what you need
     * 
     * @return string
     */
    public function getSaleTicket($token)
    {     
        $saleTicket = \App\Models\SaleTicket::select('id','note','format_id')
                        ->with('order.products')
                        ->with('contact')
                        ->where('token',$token)
                        ->first();
        
        $html = $saleTicket->getHtmlFormatView();

        // Hiding some data null|unset

        $saleTicket->important_attribute = null;
        unset($saleTicket->important_attribute);

        //Adding some default styles {ticket:styles.table} {ticket:styles.button}

        $saleTicket->styles = [
            'table'  => 'padding: 10px; border: 1px solid',
            'button' => 'rounded: 8px; border: 1px solid blue; background-color:blue; color: white',
        ];

        // Filling the text flags with the values

        $textFlags = TextFlags::fill([
            'ticket' => $saleTicket,
        ]);

        // Reading the full html and return after apply the values
        
        return $textFlags->read($html)->apply();
    }
}

```

## Author ✒️

_Guillermo Rodriguez / guillermo.rod.dev@gmail.com_

## License 📄

This project is under the license (MIT) - Look the file [LICENSE.md](LICENSE.md).