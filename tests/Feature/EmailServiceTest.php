<?php

namespace Tests\Feature;

use Carbon\Carbon;
use App\Mail\Email;
use Tests\TestCase;

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Bus;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Exceptions\EmailTemplateException;
use Database\Seeders\EmailTemplatesSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp([
            EmailTemplatesSeeder::class,
        ]);
        $this->seed();

        // Bus::fake();
    }

    public function tearDown(): void
    {
        Bus::fake();
    }

    public function test_dispatch_email_job()
    {
        Mail::fake();

        $subject = '
        <!doctype html>
        <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
          <head>
            <title>
    
            </title>
            <!--[if !mso]><!-- -->
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <!--<![endif]-->
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style type="text/css">
              #outlook a { padding:0; }
              body { margin:0;padding:0;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%; }
              table, td { border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt; }
              img { border:0;height:auto;line-height:100%; outline:none;text-decoration:none;-ms-interpolation-mode:bicubic; }
              p { display:block;margin:13px 0; }
            </style>
            <!--[if mso]>
            <xml>
            <o:OfficeDocumentSettings>
              <o:AllowPNG/>
              <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
            </xml>
            <![endif]-->
            <!--[if lte mso 11]>
            <style type="text/css">
              .mj-outlook-group-fix { width:100% !important; }
            </style>
            <![endif]-->
    
          <!--[if !mso]><!-->
            <link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css">
            <style type="text/css">
              @import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);
            </style>
          <!--<![endif]-->
    
    
    
        <style type="text/css">
          @media only screen and (min-width:480px) {
            .mj-column-per-100 { width:100% !important; max-width: 100%; }
    .mj-column-px-600 { width:600px !important; max-width: 600px; }
    .mj-column-px-400 { width:400px !important; max-width: 400px; }
    .mj-column-per-50 { width:50% !important; max-width: 50%; }
          }
        </style>
    
    
            <style type="text/css">
    
    
    
        @media only screen and (max-width:480px) {
          table.mj-full-width-mobile { width: 100% !important; }
          td.mj-full-width-mobile { width: auto !important; }
        }
    
            </style>
    
    
          </head>
          <body>
    
    
          <div
             style=""
          >
            <!-- Company Header -->
    
          <!--[if mso | IE]>
          <table
             align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600"
          >
            <tr>
              <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
          <![endif]-->
    
    
          <div  style="background:#f0f0f0;background-color:#f0f0f0;margin:0px auto;max-width:600px;">
    
            <table
               align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#f0f0f0;background-color:#f0f0f0;width:100%;"
            >
              <tbody>
                <tr>
                  <td
                     style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;"
                  >
                    <!--[if mso | IE]>
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0">
    
            <tr>
    
                <td
                   class="" style="vertical-align:top;width:600px;"
                >
              <![endif]-->
    
          <div
             class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"
          >
    
          <table
             border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"
          >
    
                <tr>
                  <td
                     align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;"
                  >
    
          <div
             style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:20px;font-style:italic;line-height:1;text-align:left;color:#626262;"
          >Health Data Research UK</div>
    
                  </td>
                </tr>
    
          </table>
    
          </div>
    
              <!--[if mso | IE]>
                </td>
    
            </tr>
    
                      </table>
                    <![endif]-->
                  </td>
                </tr>
              </tbody>
            </table>
    
          </div>
    
    
          <!--[if mso | IE]>
              </td>
            </tr>
          </table>
          <![endif]-->
    
        <!-- Image Header -->
    
          <!--[if mso | IE]>
          <table
             align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600"
          >
            <tr>
              <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
    
            <v:rect  style="width:600px;" xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false">
            <v:fill  origin="0.5, 0" position="0.5, 0" src="https://place-hold.it/600x100/000000/ffffff/grey.png" type="tile" />
            <v:textbox style="mso-fit-shape-to-text:true" inset="0,0,0,0">
          <![endif]-->
    
          <div  style="background:url(https://place-hold.it/600x100/000000/ffffff/grey.png) top center / cover no-repeat;margin:0px auto;max-width:600px;">
            <div  style="line-height:0;font-size:0;">
            <table
               align="center" background="https://place-hold.it/600x100/000000/ffffff/grey.png" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:url(https://place-hold.it/600x100/000000/ffffff/grey.png) top center / cover no-repeat;width:100%;"
            >
              <tbody>
                <tr>
                  <td
                     style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;"
                  >
                    <!--[if mso | IE]>
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0">
    
            <tr>
    
                <td
                   class="" style="vertical-align:top;width:600px;"
                >
              <![endif]-->
    
          <div
             class="mj-column-px-600 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"
          >
    
          <table
             border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"
          >
    
                <tr>
                  <td
                     align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"
                  >
    
          <div
             style="font-family:Helvetica Neue;font-size:40px;line-height:1;text-align:center;color:#ffffff;"
          >[[HEADER_TEXT]]</div>
    
                  </td>
                </tr>
    
          </table>
    
          </div>
    
              <!--[if mso | IE]>
                </td>
    
            </tr>
    
                      </table>
                    <![endif]-->
                  </td>
                </tr>
              </tbody>
            </table>
            </div>
          </div>
    
            <!--[if mso | IE]>
            </v:textbox>
          </v:rect>
    
              </td>
            </tr>
          </table>
          <![endif]-->
    
        <!-- Intro text -->
    
          <!--[if mso | IE]>
          <table
             align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600"
          >
            <tr>
              <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
          <![endif]-->
    
    
          <div  style="background:#fafafa;background-color:#fafafa;margin:0px auto;max-width:600px;">
    
            <table
               align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#fafafa;background-color:#fafafa;width:100%;"
            >
              <tbody>
                <tr>
                  <td
                     style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;"
                  >
                    <!--[if mso | IE]>
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0">
    
            <tr>
    
                <td
                   class="" style="vertical-align:top;width:400px;"
                >
              <![endif]-->
    
          <div
             class="mj-column-px-400 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"
          >
    
          <table
             border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"
          >
    
                <tr>
                  <td
                     align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;"
                  >
    
          <div
             style="font-family:Helvetica Neue;font-size:20px;font-style:italic;line-height:1;text-align:left;color:#626262;"
          >[[SUBHEADING_TEXT]]</div>
    
                  </td>
                </tr>
    
                <tr>
                  <td
                     align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;"
                  >
    
          <div
             style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#525252;"
          >Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin rutrum enim eget magna efficitur, eu semper augue semper. Aliquam erat volutpat. Cras id dui lectus. Vestibulum sed finibus lectus, sit amet suscipit nibh. Proin nec commodo purus.
                        Sed eget nulla elit. Nulla aliquet mollis faucibus.</div>
    
                  </td>
                </tr>
    
                <tr>
                  <td
                     align="center" vertical-align="middle" style="font-size:0px;padding:10px 25px;word-break:break-word;"
                  >
    
          <table
             border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;"
          >
            <tr>
              <td
                 align="center" bgcolor="#F45E43" role="presentation" style="border:none;border-radius:3px;cursor:auto;mso-padding-alt:10px 25px;background:#F45E43;" valign="middle"
              >
                <a
                   href="https://test.com/something1" style="display:inline-block;background:#F45E43;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;font-weight:normal;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:3px;" target="_blank"
                >
                  Button 1 Text
                </a>
              </td>
            </tr>
          </table>
    
                  </td>
                </tr>
    
                <tr>
                  <td
                     align="center" vertical-align="middle" style="font-size:0px;padding:10px 25px;word-break:break-word;"
                  >
    
          <table
             border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;"
          >
            <tr>
              <td
                 align="center" bgcolor="#F45E43" role="presentation" style="border:none;border-radius:3px;cursor:auto;mso-padding-alt:10px 25px;background:#F45E43;" valign="middle"
              >
                <a
                   href="https://test.com/something2" style="display:inline-block;background:#F45E43;color:#ffffff;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;font-weight:normal;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;mso-padding-alt:0px;border-radius:3px;" target="_blank"
                >
                  Button 2 Text
                </a>
              </td>
            </tr>
          </table>
    
                  </td>
                </tr>
    
          </table>
    
          </div>
    
              <!--[if mso | IE]>
                </td>
    
            </tr>
    
                      </table>
                    <![endif]-->
                  </td>
                </tr>
              </tbody>
            </table>
    
          </div>
    
    
          <!--[if mso | IE]>
              </td>
            </tr>
          </table>
          <![endif]-->
    
        <!-- Side image -->
    
          <!--[if mso | IE]>
          <table
             align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600"
          >
            <tr>
              <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
          <![endif]-->
    
    
          <div  style="background:white;background-color:white;margin:0px auto;max-width:600px;">
    
            <table
               align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:white;background-color:white;width:100%;"
            >
              <tbody>
                <tr>
                  <td
                     style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;"
                  >
                    <!--[if mso | IE]>
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0">
    
            <tr>
          <![endif]-->
          <!-- Left image -->
              <!--[if mso | IE]>
                <td
                   class="" style="vertical-align:top;width:300px;"
                >
              <![endif]-->
    
          <div
             class="mj-column-per-50 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"
          >
    
          <table
             border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"
          >
    
                <tr>
                  <td
                     align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;"
                  >
    
          <table
             border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;"
          >
            <tbody>
              <tr>
                <td  style="width:200px;">
    
          <img
             height="auto" src="https://place-hold.it/200x300/000000/ffffff/grey.png" style="border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;" width="200"
          />
    
                </td>
              </tr>
            </tbody>
          </table>
    
                  </td>
                </tr>
    
          </table>
    
          </div>
    
              <!--[if mso | IE]>
                </td>
              <![endif]-->
        <!-- right paragraph -->
              <!--[if mso | IE]>
                <td
                   class="" style="vertical-align:top;width:300px;"
                >
              <![endif]-->
    
          <div
             class="mj-column-per-50 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"
          >
    
          <table
             border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"
          >
    
                <tr>
                  <td
                     align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;"
                  >
    
          <div
             style="font-family:Helvetica Neue;font-size:20px;font-style:italic;line-height:1;text-align:left;color:#626262;"
          >[[SUBHEADING_TEXT]]</div>
    
                  </td>
                </tr>
    
                <tr>
                  <td
                     align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;"
                  >
    
          <div
             style="font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:13px;line-height:1;text-align:left;color:#525252;"
          >Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin rutrum enim eget magna efficitur, eu semper augue semper. Aliquam erat volutpat. Cras id dui lectus. Vestibulum sed finibus lectus.</div>
    
                  </td>
                </tr>
    
          </table>
    
          </div>
    
              <!--[if mso | IE]>
                </td>
    
            </tr>
    
                      </table>
                    <![endif]-->
                  </td>
                </tr>
              </tbody>
            </table>
    
          </div>
    
    
          <!--[if mso | IE]>
              </td>
            </tr>
          </table>
          <![endif]-->
    
    
          </div>
    
          </body>
        </html>
      ';

        // Http::fake([
        //     'api.mjml.io/*' => Http::response([
        //         'html' => '<p>Your HTML content here</p>',
        //     ], 200),
        // ]);
        
        $to = [
            'to' => [
                'email' => 'loki.sinclair@hdruk.ac.uk',
                'name' => 'Loki Sinclair',
            ],
        ];

        $template = EmailTemplate::where('identifier', '=', 'example_template')->first();

        $replacements = [
            '[[header_text]]' => 'Health Data Research UK',
            '[[button_text]]' => 'Click me!',
            '[[subheading_text]]' => 'Sub Heading Something or other',
        ];

        // Bus::assertNothingDispatched();

        SendEmailJob::dispatch($to, $template, $replacements);

        Mail::assertSent(Email::class, function ($mail) use ($template) {
            // var_dump($mail->to[0]['address']);
            // exit();
            // return $mail->hasTo('loki.sinclair@hdruk.ac.uk') &&
            // $mail->subject === 'Example Template' && // Adjust according to your logic
            // strpos($mail->mjmlToHtml(), 'body template') === false; // Example assertion on content
            return $mail->subject === 'Example Template' && // Adjust according to your logic
                   strpos($mail->mjmlToHtml(), 'body template') === false; // Example assertion on content
        });

        // Bus::assertDispatched(SendEmailJob::class);
    }

}
