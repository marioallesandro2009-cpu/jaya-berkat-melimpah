<x-mail::message>
# {{ __('New inquiry from the website') }}

<x-mail::table>
| | |
|:--|:--|
| **{{ __('Name') }}** | {{ str_replace('|', '¦', $lead->name) }} |
| **Email** | {{ $lead->email }} |
| **{{ __('Phone') }}** | {{ str_replace('|', '¦', (string) $lead->phone) ?: '-' }} |
| **{{ __('Company') }}** | {{ str_replace('|', '¦', (string) $lead->company) ?: '-' }} |
| **{{ __('Country') }}** | {{ str_replace('|', '¦', (string) $lead->country) ?: '-' }} |
| **{{ __('Product of interest') }}** | {{ str_replace('|', '¦', (string) $lead->product_title) ?: '-' }} |
| **{{ __('Estimated volume') }}** | {{ str_replace('|', '¦', (string) $lead->volume) ?: '-' }} |
| **{{ __('Received') }}** | {{ $lead->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB |
| **{{ __('Language') }}** | {{ $localeName }} |
</x-mail::table>

@if (filled($lead->message))
**{{ __('Message') }}**

<x-mail::panel>
{{ $lead->message }}
</x-mail::panel>
@endif

<x-mail::button :url="'mailto:'.$lead->email">
{{ __('Reply to :name', ['name' => $lead->name]) }}
</x-mail::button>

{{ __('You can also press Reply on this email: it goes straight to the sender.') }}
</x-mail::message>
