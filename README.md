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
            ]
        ]);

        // Reading the text

        $contactInfo = $textFlags->read('        
            <div class="card contact-info">
                <p>Organization: {contact_info.organization}</p>
                <p>Manager: {contact_info.manager_name}</p>
                <p>Manager Email: {contact_info.manager_email}</p>
            </div>
        ')
        ->apply();

        return response()->json([
            'contact_info' => $contactInfo
        ]);
    }

   
    /**
     * Or in some cases you need that the users uploads an custom format for a ticket, pdf, etc,,,
     * 
     * But use the blade engine can be dangerous for sql injections or query statements with the @php directive,
     * so that was the reason for i create this package
     * 
     * In this way you can return the custom user format (html) with the real value to the pdf api or do what you need
     * 
     * @return string
     */
    public function saleTicket($token)
    {     
        $saleTicket = \App\Models\SaleTicket::select('id','note','format_id')
                            ->with('order')
                            ->with('contact')
                            ->where('token',$token)
                            ->first();

        
        $customFormatPath = $saleTicket->getHtmlFormatView();
        $html             = view($customFormatPath)->render();

        // Filling the text flags with the values

        $textFlags = TextFlags::fill([
            'ticket'  => $saleTicket,
            'contact' => $saleTicket->contact,
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