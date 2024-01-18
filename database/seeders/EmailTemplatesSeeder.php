<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seeds the default email templates used throughout the system

        // Template Example

        EmailTemplate::create([
            'identifier' => 'example_template',
            'subject' => 'Example Template',
            'body' => '<mjml>
            <mj-body>
              <mj-raw>
                <!-- Company Header -->
              </mj-raw>
              <mj-section background-color="#f0f0f0">
                <mj-column>
                  <mj-text font-style="italic" font-size="20px" color="#626262">Health Data Research UK</mj-text>
                </mj-column>
              </mj-section>
              <mj-raw>
                <!-- Image Header -->
              </mj-raw>
              <mj-section background-url="https://place-hold.it/600x100/000000/ffffff/grey.png" background-size="cover" background-repeat="no-repeat">
                <mj-column width="600px">
                  <mj-text align="center" color="#fff" font-size="40px" font-family="Helvetica Neue">[[header_text]]</mj-text>
                </mj-column>
              </mj-section>
              <mj-raw>
                <!-- Intro text -->
              </mj-raw>
              <mj-section background-color="#fafafa">
                <mj-column width="400px">
                  <mj-text font-style="italic" font-size="20px" font-family="Helvetica Neue" color="#626262">Heading</mj-text>
                  <mj-text color="#525252">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin rutrum enim eget magna efficitur, eu semper augue semper. Aliquam erat volutpat. Cras id dui lectus. Vestibulum sed finibus lectus, sit amet suscipit nibh. Proin nec commodo purus.
                    Sed eget nulla elit. Nulla aliquet mollis faucibus.</mj-text>
                  <mj-button background-color="#F45E43" href="#">[[button_text]]</mj-button>
                </mj-column>
              </mj-section>
              <mj-raw>
                <!-- Side image -->
              </mj-raw>
              <mj-section background-color="white">
                <mj-raw>
                  <!-- Left image -->
                </mj-raw>
                <mj-column>
                  <mj-image width="200px" src="https://place-hold.it/200x300/000000/ffffff/grey.png"></mj-image>
                </mj-column>
                <mj-raw>
                  <!-- right paragraph -->
                </mj-raw>
                <mj-column>
                  <mj-text font-style="italic" font-size="20px" font-family="Helvetica Neue" color="#626262">[[subheading_text]]</mj-text>
                  <mj-text color="#525252">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin rutrum enim eget magna efficitur, eu semper augue semper. Aliquam erat volutpat. Cras id dui lectus. Vestibulum sed finibus lectus.</mj-text>
                </mj-column>
              </mj-section>
            </mj-body>
          </mjml>',
        ]);

      // custodian.team.admin - assign
      EmailTemplate::create([
        'identifier' => 'custodian.team.admin.assign',
        'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Team Admin',
        'body' => '
          <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Custodian Team Admin role has been assigned</mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text font-weight="bold" line-height="20px">
                            [[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Team Admin.
                        </mj-text>
                        <mj-text line-height="20px">
                            You can now add, remove and change the roles of other members of the [[TEAM_NAME]] team.
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
          </mjml>
          ',
      ]);

      // custodian.team.admin - remove
      EmailTemplate::create([
        'identifier' => 'custodian.team.admin.remove',
        'subject' => 'You have been removed as a Team Admin for the [[TEAM_NAME]] team on the Gateway.',
        'body' => '
          <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Custodian Team Admin role has been removed</mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text font-weight="bold" line-height="20px">
                            You have been removed as a Team Admin for the [[TEAM_NAME]] team on the Gateway.
                        </mj-text>
                        <mj-text>
                            You can no longer:
                            <ul>
                                <li style="line-height:20px;height:auto;">Add roles of other members of the [[TEAM_NAME]] team.</li>
                                <li style="line-height:20px;height:auto;">Remove roles of other members of the [[TEAM_NAME]] team.</li>
                                <li style="line-height:20px;height:auto;">Change the roles of other members of the [[TEAM_NAME]] team.</li>
                            </ul>
                        </mj-text>
                        <mj-text line-height="20px">
                            For more information, please contact a Team Admin for your team:<br>
                            [[LIST_TEAM_ADMINS]]
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
          </mjml>
          ',
      ]);

      // custodian.dar.manager - assign
      EmailTemplate::create([
        'identifier' => 'custodian.dar.manager.assign',
        'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Data Access Manager',
        'body' => '
          <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">DAR Manager role has been assigned</mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text font-weight="bold" line-height="20px">
                            [[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Data Access Manager.
                        </mj-text>
                        <mj-text>
                            You can now:
                            <ul>
                                <li style="line-height:20px;height:auto;">Manage data access requests through the Gateway for the [[TEAM_NAME]] team.</li>
                                <li style="line-height:20px;height:auto;">You can create and assign workflows, process applications, and communicate with applicants through the Gateway.</li>
                                <li style="line-height:20px;height:auto;">You can also add and remove other team members, and assign sections of the data access review workflow to them.</li>
                            </ul>
                        </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="#">View data access requests</mj-button>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
          </mjml>
          ',
      ]);

      // custodian.dar.manager - remove
      EmailTemplate::create([
        'identifier' => 'custodian.dar.manager.remove',
        'subject' => 'You have been removed as a Data Access Manager for the [[TEAM_NAME]] team on the Gateway.',
        'body' => '
          <mjml>
            <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                    }
                </mj-style>
            </mj-head>
            <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>

                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">DAR Manager role has been removed</mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column width="100%">
                        <mj-text font-weight="bold" line-height="20px">
                            You have been removed as a Data Access Manager for the [[TEAM_NAME]] team on the Gateway.
                        </mj-text>
                        <mj-text>
                            You can no longer:
                            <ul>
                                <li style="line-height:20px;height:auto;">Manage data access requests through the Gateway for the [[TEAM_NAME]] team.</li>
                                <li style="line-height:20px;height:auto;">Create and assign workflows, process applications, and communicate with applicants through the Gateway.</li>
                                <li style="line-height:20px;height:auto;">Add and remove other team members.</li>
                                <li style="line-height:20px;height:auto;">Assign sections of the data access review workflow to them.</li>
                            </ul>
                        </mj-text>
                        <mj-text line-height="20px">
                            For more information, please contact a Team Admin for your team:<br>
                            [[LIST_TEAM_ADMINS]]
                        </mj-text>
                    </mj-column>
                </mj-section>

                <mj-section>
                    <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#525252" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                </mj-section>

            </mj-body>
          </mjml>
        ',
      ]);

      // reviewer - assign
      EmailTemplate::create([
        'identifier' => 'reviewer.assign',
        'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Reviewer',
        'body' => '
          <mjml>
              <mj-head>
                  <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                  <mj-style inline="inline">
                      .main-button {
                          padding:10px;
                          width:auto;
                          -webkit-border-radius:5px;
                          -moz-border-radius:5px;
                          border-radius:5px;
                          color:#FFFFFF;
                      }
                  </mj-style>
              </mj-head>
              <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                  <mj-section background-color="#ffffff">
                      <mj-column>
                          <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                      </mj-column>
                  </mj-section>

                  <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                      <mj-column width="100%">
                          <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">DAR Reviewer role has been assigned</mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column width="100%">
                          <mj-text font-weight="bold" line-height="20px">
                              [[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Reviewer.
                          </mj-text>
                          <mj-text>
                              You can now:
                              <ul>
                                  <li style="line-height:20px;height:auto;">Review sections of a data access request that have been assigned to you by a Data Access Manager for the [[TEAM_NAME]] team.</li>
                                  <li style="line-height:20px;height:auto;">You can process applications and communicate with applicants through the Gateway.</li>
                              </ul>
                          </mj-text>
                          <mj-button css-class="main-section" background-color="#00ACCA" href="#">View data access requests</mj-button>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column>
                          <mj-text align="center">
                              <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                          </mj-text>
                          <mj-text color="#525252" align="center">
                              @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                          </mj-text>
                      </mj-column>
                  </mj-section>

              </mj-body>
          </mjml>
          ',
      ]);

      // reviewer - remove
      EmailTemplate::create([
        'identifier' => 'reviewer.remove',
        'subject' => 'You have been removed as a Reviewer for the [[TEAM_NAME]] team on the Gateway.',
        'body' => '
          <mjml>
              <mj-head>
                  <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                  <mj-style inline="inline">
                      .main-button {
                          padding:10px;
                          width:auto;
                          -webkit-border-radius:5px;
                          -moz-border-radius:5px;
                          border-radius:5px;
                          color:#FFFFFF;
                      }
                  </mj-style>
              </mj-head>
              <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                  <mj-section background-color="#ffffff">
                      <mj-column>
                          <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                      </mj-column>
                  </mj-section>

                  <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                      <mj-column width="100%">
                          <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">DAR Manager role has been removed</mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column width="100%">
                          <mj-text font-weight="bold" line-height="20px">
                              You have been removed as a Data Access Manager for the [[TEAM_NAME]] team on the Gateway.
                          </mj-text>
                          <mj-text>
                              You can no longer:
                              <ul>
                                  <li style="line-height:20px;height:auto;">Manage data access requests through the Gateway for the [[TEAM_NAME]] team.</li>
                                  <li style="line-height:20px;height:auto;">Create and assign workflows, process applications, and communicate with applicants through the Gateway.</li>
                                  <li style="line-height:20px;height:auto;">Add and remove other team members.</li>
                                  <li style="line-height:20px;height:auto;">Assign sections of the data access review workflow to them.</li>
                              </ul>
                          </mj-text>
                          <mj-text line-height="20px">
                              For more information, please contact a Team Admin for your team:<br>
                              [[LIST_TEAM_ADMINS]]
                          </mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column>
                          <mj-text align="center">
                              <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                          </mj-text>
                          <mj-text color="#525252" align="center">
                              @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                          </mj-text>
                      </mj-column>
                  </mj-section>

              </mj-body>
          </mjml>
          ',
      ]);

      // developer - assign
      EmailTemplate::create([
        'identifier' => 'developer.assign',
        'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Developer',
        'body' => '
          <mjml>
              <mj-head>
                  <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                  <mj-style inline="inline">
                      .main-button {
                          padding:10px;
                          width:auto;
                          -webkit-border-radius:5px;
                          -moz-border-radius:5px;
                          border-radius:5px;
                          color:#FFFFFF;
                      }
                  </mj-style>
              </mj-head>
              <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                  <mj-section background-color="#ffffff">
                      <mj-column>
                          <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                      </mj-column>
                  </mj-section>

                  <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                      <mj-column width="100%">
                          <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Developer role has been assigned</mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column width="100%">
                          <mj-text font-weight="bold" line-height="20px">
                              [[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Developer.
                          </mj-text>
                          <mj-text line-height="20px">
                              You can now perform technical functions like creating and managing api-keys and app integration for the [[TEAM_NAME]] team on the Gateway.
                          </mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column>
                          <mj-text align="center">
                              <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                          </mj-text>
                          <mj-text color="#525252" align="center">
                              @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                          </mj-text>
                      </mj-column>
                  </mj-section>

              </mj-body>
          </mjml>
          ',
      ]);

      // developer - remove
      EmailTemplate::create([
        'identifier' => 'developer.remove',
        'subject' => 'You have been removed as a Developer for the [[TEAM_NAME]] team on the Gateway.',
        'body' => '
          <mjml>
              <mj-head>
                  <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                  <mj-style inline="inline">
                      .main-button {
                          padding:10px;
                          width:auto;
                          -webkit-border-radius:5px;
                          -moz-border-radius:5px;
                          border-radius:5px;
                          color:#FFFFFF;
                      }
                  </mj-style>
              </mj-head>
              <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                  <mj-section background-color="#ffffff">
                      <mj-column>
                          <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                      </mj-column>
                  </mj-section>

                  <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                      <mj-column width="100%">
                          <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Developer role has been removed</mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column width="100%">
                          <mj-text font-weight="bold" line-height="20px">
                              You have been removed as a Developer for the [[TEAM_NAME]] team on the Gateway.
                          </mj-text>
                          <mj-text line-height="20px">
                              You can no longer perform technical functions like creating and managing api-keys and app integration for the [[TEAM_NAME]] team on the Gateway.
                          </mj-text>
                          <mj-text line-height="20px">
                              For more information, please contact a Team Admin for your team:<br>
                              [[LIST_TEAM_ADMINS]]
                          </mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column>
                          <mj-text align="center">
                              <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                          </mj-text>
                          <mj-text color="#525252" align="center">
                              @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                          </mj-text>
                      </mj-column>
                  </mj-section>

              </mj-body>
          </mjml>
            ',
      ]);

      // metadata_editor - assign
      EmailTemplate::create([
        'identifier' => 'metadata_editor.assign',
        'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Metadata Editor',
        'body' => '
          <mjml>
              <mj-head>
                  <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                  <mj-style inline="inline">
                      .main-button {
                          padding:10px;
                          width:auto;
                          -webkit-border-radius:5px;
                          -moz-border-radius:5px;
                          border-radius:5px;
                          color:#FFFFFF;
                      }
                  </mj-style>
              </mj-head>
              <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                  <mj-section background-color="#ffffff">
                      <mj-column>
                          <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                      </mj-column>
                  </mj-section>

                  <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                      <mj-column width="100%">
                          <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Metadata Editor role has been assigned</mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column width="100%">
                          <mj-text font-weight="bold" line-height="20px">
                              [[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Metadata Editor.
                          </mj-text>
                          <mj-text>
                              You can now:
                              <ul>
                                  <li style="line-height:20px;height:auto;">Onboard information about datasets uploaded by the [[TEAM_NAME]] team.</li>
                                  <li style="line-height:20px;height:auto;">Manage information about datasets uploaded by the [[TEAM_NAME]] team.</li>
                              </ul>
                          </mj-text>
                          <mj-button css-class="main-section" background-color="#00ACCA" href="#">View datasets</mj-button>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column>
                          <mj-text align="center">
                              <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                          </mj-text>
                          <mj-text color="#525252" align="center">
                              @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                          </mj-text>
                      </mj-column>
                  </mj-section>

              </mj-body>
          </mjml>
          ',
      ]);

      // metadata_editor - remove
      EmailTemplate::create([
        'identifier' => 'metadata_editor.remove',
        'subject' => 'You have been removed as a Metadata Editor for the [[TEAM_NAME]] team on the Gateway.',
        'body' => '
          <mjml>
              <mj-head>
                  <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                  <mj-style inline="inline">
                      .main-button {
                          padding:10px;
                          width:auto;
                          -webkit-border-radius:5px;
                          -moz-border-radius:5px;
                          border-radius:5px;
                          color:#FFFFFF;
                      }
                  </mj-style>
              </mj-head>
              <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                  <mj-section background-color="#ffffff">
                      <mj-column>
                          <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="225px" />
                      </mj-column>
                  </mj-section>

                  <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                      <mj-column width="100%">
                          <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Metadata Editor role has been removed</mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column width="100%">
                          <mj-text font-weight="bold" line-height="20px">
                              You have been removed as a Metadata Editor for the [[TEAM_NAME]] team on the Gateway.
                          </mj-text>
                          <mj-text line-height="20px">
                              You can no longer onboard and manage information about datasets upload by the [[TEAM_NAME]] team.
                          </mj-text>
                          <mj-text line-height="20px">
                              For more information, please contact a Team Admin for your team:<br>
                              [[LIST_TEAM_ADMINS]]
                          </mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column>
                          <mj-text align="center">
                              <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                          </mj-text>
                          <mj-text color="#525252" align="center">
                              @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                          </mj-text>
                      </mj-column>
                  </mj-section>

              </mj-body>
          </mjml>
            ',
      ]);

      // custodian.metadata.manager - assign
      EmailTemplate::create([
        'identifier' => 'custodian.metadata.manager.assign',
        'subject' => '[[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Metadata Manager',
        'body' => '
          <mjml>
              <mj-head>
                  <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                  <mj-style inline="inline">
                      .main-button {
                          padding:10px;
                          width:auto;
                          -webkit-border-radius:5px;
                          -moz-border-radius:5px;
                          border-radius:5px;
                          color:#FFFFFF;
                      }
                  </mj-style>
              </mj-head>
              <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                  <mj-section background-color="#ffffff">
                      <mj-column>
                          <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                      </mj-column>
                  </mj-section>

                  <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                      <mj-column width="100%">
                          <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Metadata Manager role has been assigned</mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column width="100%">
                          <mj-text font-weight="bold" line-height="20px">
                              [[ASSIGNER_NAME]] has added you to the [[TEAM_NAME]] publishing team on the Gateway as a Metadata Manager
                          </mj-text>
                          <mj-text>
                              You can now:
                              <ul>
                                  <li style="line-height:20px;height:auto;">Onboard and manage information about datasets uploaded by the [[TEAM_NAME]] team.</li>
                                  <li style="line-height:20px;height:auto;">Add and remove other team members with editor permissions.</li>
                              </ul>
                          </mj-text>
                          <mj-button css-class="main-section" background-color="#00ACCA" href="#">View datasets</mj-button>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column>
                          <mj-text align="center">
                              <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                          </mj-text>
                          <mj-text color="#525252" align="center">
                              @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                          </mj-text>
                      </mj-column>
                  </mj-section>

              </mj-body>
          </mjml>
          ',
      ]);

      // custodian.metadata.manager - remove
      EmailTemplate::create([
        'identifier' => 'custodian.metadata.manager.remove',
        'subject' => 'You have been removed as a Metadata Manager for the [[TEAM_NAME]] team on the Gateway',
        'body' => '
          <mjml>
              <mj-head>
                  <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                  <mj-style inline="inline">
                      .main-button {
                          padding:10px;
                          width:auto;
                          -webkit-border-radius:5px;
                          -moz-border-radius:5px;
                          border-radius:5px;
                          color:#FFFFFF;
                      }
                  </mj-style>
              </mj-head>
              <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                  <mj-section background-color="#ffffff">
                      <mj-column>
                          <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="225px" />
                      </mj-column>
                  </mj-section>

                  <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                      <mj-column width="100%">
                          <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Metadata Manager role has been removed</mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column width="100%">
                          <mj-text font-weight="bold" line-height="20px">
                              You have been removed as a Metadata Manager for the [[TEAM_NAME]] team on the Gateway.
                          </mj-text>
                          <mj-text>
                              You can no longer:
                              <ul>
                                  <li style="line-height:20px;height:auto;">Onboard and manage information about datasets uploaded by the [[TEAM_NAME]] team.</li>
                                  <li style="line-height:20px;height:auto;">Add and remove other team members with editor permissions.</li>
                              </ul>
                          </mj-text>
                          <mj-text line-height="20px">
                              For more information, please contact a Team Admin for your team:<br>
                              [[LIST_TEAM_ADMINS]]
                          </mj-text>
                      </mj-column>
                  </mj-section>

                  <mj-section>
                      <mj-column>
                          <mj-text align="center">
                              <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                          </mj-text>
                          <mj-text color="#525252" align="center">
                              @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                          </mj-text>
                      </mj-column>
                  </mj-section>

              </mj-body>
          </mjml>
          ',
      ]);

        // hdruk.superadmin - assign
        EmailTemplate::create([
            'identifier' => 'hdruk.superadmin.assign',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.superadmin.assign',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Mhdruk.superadmin.assign role has been assigned</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.superadmin - remove
        EmailTemplate::create([
            'identifier' => 'hdruk.superadmin.remove',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.superadmin.remove',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.superadmin.assign role has been removed</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.admin - assign
        EmailTemplate::create([
            'identifier' => 'hdruk.admin.assign',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.admin.assign',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.admin.assign role has been assigned</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.admin - remove
        EmailTemplate::create([
            'identifier' => 'hdruk.admin.remove',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.admin.remove',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.admin.assign role has been removed</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.metadata - assign
        EmailTemplate::create([
            'identifier' => 'hdruk.metadata.assign',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.metadata.assign',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.metadata.assign role has been assigned</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.metadata - remove
        EmailTemplate::create([
            'identifier' => 'hdruk.metadata.remove',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.metadata.remove',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.metadata.assign role has been removed</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.dar - assign
        EmailTemplate::create([
            'identifier' => 'hdruk.dar.assign',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.dar.assign',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.dar.assign role has been assigned</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.dar - remove
        EmailTemplate::create([
            'identifier' => 'hdruk.dar.remove',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.dar.remove',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.dar.assign role has been removed</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.custodian - assign
        EmailTemplate::create([
            'identifier' => 'hdruk.custodian.assign',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.custodian.assign',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.custodian.assign role has been assigned</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        // hdruk.custodian - remove
        EmailTemplate::create([
            'identifier' => 'hdruk.custodian.remove',
            'subject' => '[[ASSIGNER_NAME]] - hdruk.custodian.remove',
            'body' => '
                <mjml>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                            <mj-column>
                                <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                            </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                            <mj-column width="100%">
                                <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">hdruk.custodian.assign role has been removed</mj-text>
                            </mj-column>
                        </mj-section>

                        <mj-section>
                            <mj-column>
                                <mj-text align="center">
                                    <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                                </mj-text>
                                <mj-text color="#525252" align="center">
                                    @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                                </mj-text>
                            </mj-column>
                        </mj-section>

                    </mj-body>
                </mjml>
          ',
        ]);

        $apiIntegrationDevCreateBody = '
            <mjml>
                <mj-head>
                    <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                    <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                    </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                    <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                    </mj-section>

                    <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Congratulations! A new integration has been created for<br> [[TEAM_NAME]]
                        </mj-text>
                    </mj-column>
                    </mj-section>
                    <mj-section>
                    <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Dear [[USER_FIRST_NAME]],<br></br>
                            [[API_NAME]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                        </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="#">View app integrations</mj-button>
                    </mj-column>
                    </mj-section>
                    <mj-section>
                    <mj-column>
                        <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                    </mj-section>
                </mj-body>
            </mjml>
        ';

        // APIintegration - create - developer
        EmailTemplate::create([
            'identifier' => 'apiintegration.developer.create',
            'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
            'body' => $apiIntegrationDevCreateBody,
        ]);

        // APIintegration - create - team.admin
        EmailTemplate::create([
            'identifier' => 'apiintegration.team.admin.create',
            'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
            'body' => $apiIntegrationDevCreateBody,
        ]);

        // APIintegration - create - other roles
        EmailTemplate::create([
            'identifier' => 'apiintegration.other.create',
            'subject' => '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.',
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                        .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                        }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                        <mj-column>
                            <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                            <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Congratulations! A new integration has been created for<br> [[TEAM_NAME]]
                            </mj-text>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                                Dear [[USER_FIRST_NAME]],<br></br>
                                [[API_NAME]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                                To review or edit the integration, contact your Team Administrator(s) or Developer(s):<br></br>
                                [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                            </mj-text>
                            <mj-button css-class="main-section" background-color="#00ACCA" href="#">View app integrations</mj-button>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                            </mj-text>
                            <mj-text color="#3C3C3B" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                            </mj-text>
                        </mj-column>
                        </mj-section>
                    </mj-body>
                    </mjml>
            ',
        ]);










        $fmaIntegrationCreateSubject = '[[API_NAME]] has been added as an API Integration to the [[TEAM_NAME]] team on the Gateway.';

        $fmaIntegrationDevCreateBody = '
            <mjml>
                <mj-head>
                    <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                    <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                    </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                    <mj-section background-color="#ffffff">
                    <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                    </mj-section>

                    <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Congratulations! A new integration has been created for<br> [[TEAM_NAME]]
                        </mj-text>
                    </mj-column>
                    </mj-section>
                    <mj-section>
                    <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Dear [[USER_FIRST_NAME]],<br></br>
                            An integration of [[INTEGRATION_TYPE]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                        </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="#">View app integrations</mj-button>
                    </mj-column>
                    </mj-section>
                    <mj-section>
                    <mj-column>
                        <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                    </mj-column>
                    </mj-section>
                </mj-body>
            </mjml>
        ';

        // FMAintegration - create - developer
        EmailTemplate::create([
            'identifier' => 'fmaintegration.developer.create',
            'subject' => $fmaIntegrationCreateSubject,
            'body' => $fmaIntegrationDevCreateBody,
        ]);

        // FMAintegration - create - team.admin
        EmailTemplate::create([
            'identifier' => 'fmaintegration.team.admin.create',
            'subject' => $fmaIntegrationCreateSubject,
            'body' => $fmaIntegrationDevCreateBody,
        ]);

        // FMAintegration - create - other roles
        EmailTemplate::create([
            'identifier' => 'fmaintegration.other.create',
            'subject' => $fmaIntegrationCreateSubject,
            'body' => '
                <mjml>
                <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                    <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>
            
                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">Congratulations! A new integration has been created for<br> [[TEAM_NAME]]
                    </mj-text>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        An integration of [[INTEGRATION_TYPE]] has been created to enable automated integration with the HDR Innovation Gateway.<br></br>
                        To review or edit the integration, contact your Team Administrator(s) or Developer(s):<br></br>
                        [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="#">View federated integrations</mj-button>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                    </mj-text>
                    <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                    </mj-text>
                    </mj-column>
                </mj-section>
                </mj-body>
            </mjml>
            ',
        ]);




        $integrationDisabledSubject = 'An integration has been disabled for the [[TEAM_NAME]] team on the Gateway.';
        
        $integrationDevDisabledBody = '
            <mjml>
                <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                    <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>
            
                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was disabled on the Gateway for<br> [[TEAM_NAME]].
                    </mj-text>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="#">View integrations</mj-button>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                    </mj-text>
                    <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                    </mj-text>
                    </mj-column>
                </mj-section>
                </mj-body>
            </mjml>
        ';

        // Integrations - disabled - developer
        EmailTemplate::create([
            'identifier' => 'fmaintegration.developer.disable',
            'subject' => $integrationDisabledSubject,
            'body' => $integrationDevDisabledBody,
        ]);

        // Integrations - disabled - team.admin
        EmailTemplate::create([
            'identifier' => 'fmaintegration.team.admin.disable',
            'subject' => $integrationDisabledSubject,
            'body' => $integrationDevDisabledBody,
        ]);

        // Integrations - disabled - other roles
        EmailTemplate::create([
            'identifier' => 'fmaintegration.other.disable',
            'subject' => $integrationDisabledSubject,
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                        .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                        }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                        <mj-column>
                            <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                            <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was disabled on the Gateway for<br> [[TEAM_NAME]].
                            </mj-text>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                                Dear [[USER_FIRST_NAME]],<br></br>
                                This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                                To review or edit the integration, contact your Team Administrator(s) or Developer(s):
                    [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                            </mj-text>
                            <mj-button css-class="main-section" background-color="#00ACCA" href="#">View integrations</mj-button>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                            </mj-text>
                            <mj-text color="#3C3C3B" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                            </mj-text>
                        </mj-column>
                        </mj-section>
                    </mj-body>
                </mjml>
            ',
        ]);






        $integrationDeletedSubject = 'An integration has been deleted for the [[TEAM_NAME]] team on the Gateway.';
        
        $integrationDevDeletedBody = '
            <mjml>
                <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                    <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>
            
                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was disabled on the Gateway for<br> [[TEAM_NAME]].
                    </mj-text>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="#">View integrations</mj-button>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                    </mj-text>
                    <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                    </mj-text>
                    </mj-column>
                </mj-section>
                </mj-body>
            </mjml>
        ';

        // Integrations - deleted - developer
        EmailTemplate::create([
            'identifier' => 'integration.developer.delete',
            'subject' => $integrationDeletedSubject,
            'body' => $integrationDevDeletedBody,
        ]);

        // Integrations - deleted - team.admin
        EmailTemplate::create([
            'identifier' => 'integration.team.admin.delete',
            'subject' => $integrationDeletedSubject,
            'body' => $integrationDevDeletedBody,
        ]);

        // Integrations - deleted - other roles
        EmailTemplate::create([
            'identifier' => 'integration.other.delete',
            'subject' => $integrationDeleteddSubject,
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                        .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                        }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                        <mj-column>
                            <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                            <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was disabled on the Gateway for<br> [[TEAM_NAME]].
                            </mj-text>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                                Dear [[USER_FIRST_NAME]],<br></br>
                                This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                                To review or edit the integration, contact your Team Administrator(s) or Developer(s):
                    [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                            </mj-text>
                            <mj-button css-class="main-section" background-color="#00ACCA" href="#">View integrations</mj-button>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                            </mj-text>
                            <mj-text color="#3C3C3B" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                            </mj-text>
                        </mj-column>
                        </mj-section>
                    </mj-body>
                </mjml>
            ',
        ]);






        $integrationDeletedSubject = 'An integration has been deleted for the [[TEAM_NAME]] team on the Gateway.';
        
        $integrationDeletedDevBody = '
            <mjml>
                <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                    <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>
            
                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was disabled on the Gateway for<br> [[TEAM_NAME]].
                    </mj-text>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="#">View integrations</mj-button>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                    </mj-text>
                    <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                    </mj-text>
                    </mj-column>
                </mj-section>
                </mj-body>
            </mjml>
        ';

        // Integrations - deleted - developer
        EmailTemplate::create([
            'identifier' => 'integration.developer.delete',
            'subject' => $integrationDeletedSubject,
            'body' => $integrationDeletedDevBody,
        ]);

        // Integrations - deleted - team.admin
        EmailTemplate::create([
            'identifier' => 'integration.team.admin.delete',
            'subject' => $integrationDeletedSubject,
            'body' => $integrationDeletedDevBody,
        ]);

        // Integrations - deleted - other roles
        EmailTemplate::create([
            'identifier' => 'integration.other.delete',
            'subject' => $integrationDeletedSubject,
            'body' => '
                <mjml>
                    <mj-head>
                        <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                        <mj-style inline="inline">
                        .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                        }
                        </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                        <mj-section background-color="#ffffff">
                        <mj-column>
                            <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                        </mj-section>

                        <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                            <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was disabled on the Gateway for<br> [[TEAM_NAME]].
                            </mj-text>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                                Dear [[USER_FIRST_NAME]],<br></br>
                                This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                                To review or edit the integration, contact your Team Administrator(s) or Developer(s):
                    [[LIST_TEAM_ADMINS_AND_DEVELOPERS]]
                            </mj-text>
                            <mj-button css-class="main-section" background-color="#00ACCA" href="#">View integrations</mj-button>
                        </mj-column>
                        </mj-section>
                        <mj-section>
                        <mj-column>
                            <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                            </mj-text>
                            <mj-text color="#3C3C3B" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                            </mj-text>
                        </mj-column>
                        </mj-section>
                    </mj-body>
                </mjml>
            ',
        ]);




        $syncErrorSubject = 'An automation error occurred for the [[TEAM_NAME]] team on the Gateway.';
        
        $syncErrorDevBody = '
            <mjml>
                <mj-head>
                <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                <mj-style inline="inline">
                    .main-button {
                    padding:10px;
                    width:auto;
                    -webkit-border-radius:5px;
                    -moz-border-radius:5px;
                    border-radius:5px;
                    color:#FFFFFF;
                    }
                </mj-style>
                </mj-head>
                <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                <mj-section background-color="#ffffff">
                    <mj-column>
                    <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                    </mj-column>
                </mj-section>
            
                <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                    <mj-column width="100%">
                    <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An integration was disabled on the Gateway for<br> [[TEAM_NAME]].
                    </mj-text>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                        Dear [[USER_FIRST_NAME]],<br></br>
                        This is an automated notification that [[DISABLER]] disabled an integration on the HDR Innovation Gateway.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.<br></br>
                    </mj-text>
                    <mj-button css-class="main-section" background-color="#00ACCA" href="#">View integrations</mj-button>
                    </mj-column>
                </mj-section>
                <mj-section>
                    <mj-column>
                    <mj-text align="center">
                        <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                    </mj-text>
                    <mj-text color="#3C3C3B" align="center">
                        @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                    </mj-text>
                    </mj-column>
                </mj-section>
                </mj-body>
            </mjml>
        ';

        // Sync Error - developer
        EmailTemplate::create([
            'identifier' => 'integration.developer.error',
            'subject' => $syncErrorSubject,
            'body' => $syncErrorDevBody,
        ]);

        // Sync Error - team.admin
        EmailTemplate::create([
            'identifier' => 'integration.team.admin.error',
            'subject' => $syncErrorSubject,
            'body' => $syncErrorDevBody,
        ]);

        // Sync Error - other roles
        EmailTemplate::create([
            'identifier' => 'integration.other.error',
            'subject' => $syncErrorSubject,
            'body' => '
                <mjml>
                    <mj-head>
                    <mj-font name="Museo Sans Rounded" href="https://fonts.cdnfonts.com/css/museo-sans-rounded" />
                    <mj-style inline="inline">
                        .main-button {
                        padding:10px;
                        width:auto;
                        -webkit-border-radius:5px;
                        -moz-border-radius:5px;
                        border-radius:5px;
                        color:#FFFFFF;
                        }
                    </mj-style>
                    </mj-head>
                    <mj-body background-color="#FFFFFF" style="font-family:Museo Sans Rounded,sans-serif;font-size:14px; color:#3C3C3B">
                    <mj-section background-color="#ffffff">
                        <mj-column>
                        <mj-image src="https://storage.googleapis.com/public_files_dev/hdruk_logo_email.jpg" href="https://web.www.healthdatagateway.org" padding="10px 0" alt="" align="center" width="226px" />
                        </mj-column>
                    </mj-section>
                
                    <mj-section background-url="https://storage.googleapis.com/public_files_dev/hdruk_header_email.jpg" background-size="cover" background-repeat="no-repeat">
                        <mj-column width="100%">
                        <mj-text align="center" color="#fff" font-size="24px" padding="30px 0">An automation error occurred for an integration on the Gateway for<br> [[TEAM_NAME]].
                        </mj-text>
                        </mj-column>
                    </mj-section>
                    <mj-section>
                        <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Dear [[USER_FIRST_NAME]],<br></br>
                            This is an automated notification that on [[DATE_OF_ERROR]]  there was an error during the scheduled cloud run for the [[INTEGRATION_TYPE]] integration. Summary of the synchronisations is below.<br></br>
                            To review or edit the integration, click the link below or visit your account on the Gateway.
                        </mj-text>
                        <mj-button css-class="main-section" background-color="#00ACCA" href="#">View integrations</mj-button>
                        </mj-column>
                    </mj-section>
                    <mj-section>
                        <mj-column>
                        <mj-text align="left" color="#3C3C3B" font-family="Museo Sans Rounded,sans-serif">
                            Errors:<br></br>
                            [[LIST_OF_ERRORS]]<br></br>
                            Success:<br></br>
                            [[LIST_OF_SUCCESS]]<br></br>
                        </mj-text>
                        </mj-column>
                    </mj-section>
                    <mj-section>
                        <mj-column>
                        <mj-text align="center">
                            <a style="text-decoration:none" href="https://web.www.healthdatagateway.org">www.healthdatagateway.org</a>
                        </mj-text>
                        <mj-text color="#3C3C3B" align="center">
                            @HDR UK [[CURRENT_YEAR]]. All rights reserved.
                        </mj-text>
                        </mj-column>
                    </mj-section>
                    </mj-body>
                </mjml>
            ',
        ]);
    }
}
