<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Team;
use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use Illuminate\Database\Seeder;

class SINGDataProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dpc = DataProviderColl::create([
            'enabled' => 1,
            'name' => 'Demo Data Custodian Network',
            'summary' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque a gravida sapien. Praesent ac elit et ipsum efficitur ultricies quis sed nibh. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Maecenas vitae tincidunt nisi. Sed velit augue, imperdiet ut accumsan cursus, tincidunt vel elit. Ut elementum, elit eget congue blandit, urna nunc venenatis ante, sit amet placerat dui tellus vitae odio. Donec faucibus accumsan nunc, bibendum ultrices enim eleifend at. Fusce posuere arcu vel magna cursus, et scelerisque nunc dapibus. Sed sagittis, nisi ut aliquet eleifend, nisi lacus suscipit nisi, vitae vestibulum massa ligula feugiat sapien.
                Sed at arcu augue. Quisque tempor vestibulum felis, at rhoncus arcu eleifend eget. Pellentesque luctus diam a dictum euismod. Maecenas suscipit, est tempor condimentum commodo, ligula nibh blandit sapien, eu finibus urna turpis vel nisi. Duis sollicitudin, lacus vitae congue pellentesque, nisi libero tincidunt sem, ac hendrerit erat magna ut elit. Nullam vel leo in dolor dapibus posuere.',
            'img_url' => 'https://www.hdruk.ac.uk/wp-content/uploads/2023/01/thumb_320-1-320x209.jpg',
            'url' => 'https://www.hdruk.ac.uk/',
        ]);

        $team = Team::create([
            'name' => 'Demo Data Custodian',
            'enabled' => 1,
            'allows_messaging' => 1,
            'workflow_enabled' => 1,
            'access_requests_management' => 0,
            'uses_5_safes' => 1,
            'is_admin' => 0,
            'member_of' => 'Demo Data Custodian Network',
            'contact_point' => 'stephen.lavenberg@hdruk.ac.uk',
            'application_form_updated_by' => '',
            'application_form_updated_on' => Carbon::now(),
            'mongo_object_id' => '',
            'notification_status' => 1,
            'is_question_bank' => 1,
            'team_logo' => 'https://storage.googleapis.com/hdruk-gateway_prod-cms/web-assets/search_image_gteway_news.png?mtime=20220802121343&focal=none',
            'introduction' => 'Demo Data Custodian is an illustrative example of how a Data Custodian is represented on the Health Data Research Gateway. This introduction section is a place to give a background on the Data Custodian and the services it provides.',
            'dar_modal_content' => 'Guide when applying for access to datasets from Demo Data Custodian: Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque a gravida sapien. Praesent ac elit et ipsum efficitur ultricies quis sed nibh. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Maecenas vitae tincidunt nisi. Sed velit augue, imperdiet ut accumsan cursus, tincidunt vel elit. Ut elementum, elit eget congue blandit, urna nunc venenatis ante, sit amet placerat dui tellus vitae odio. Donec faucibus accumsan nunc, bibendum ultrices enim eleifend at. Fusce posuere arcu vel magna cursus, et scelerisque nunc dapibus. Sed sagittis, nisi ut aliquet eleifend, nisi lacus suscipit nisi, vitae vestibulum massa ligula feugiat sapien.
                Sed at arcu augue. Quisque tempor vestibulum felis, at rhoncus arcu eleifend eget. Pellentesque luctus diam a dictum euismod. Maecenas suscipit, est tempor condimentum commodo, ligula nibh blandit sapien, eu finibus urna turpis vel nisi. Duis sollicitudin, lacus vitae congue pellentesque, nisi libero tincidunt sem, ac hendrerit erat magna ut elit. Nullam vel leo in dolor dapibus posuere. Nam eget augue sit amet ante molestie sagittis. Proin tempor vulputate maximus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Aenean in sapien porttitor sem sodales pharetra sed eu purus. Nam ac magna turpis. Etiam fermentum in ex ac consequat. Quisque vitae sem eget massa rhoncus maximus a vitae purus. Sed at ipsum et diam ornare dapibus vitae sit amet nisl. Mauris non accumsan odio.',
        ]);

        DataProviderCollHasTeam::create([
            'data_provider_coll_id' => $dpc->id,
            'team_id' => $team->id,
        ]);
    }
}
