<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Aruba', 'iso_code' => 'AW', 'iso3' => 'ABW', 'is_default' => false],
            ['name' => 'Afghanistan', 'iso_code' => 'AF', 'iso3' => 'AFG', 'is_default' => false],
            ['name' => 'Angola', 'iso_code' => 'AO', 'iso3' => 'AGO', 'is_default' => false],
            ['name' => 'Anguilla', 'iso_code' => 'AI', 'iso3' => 'AIA', 'is_default' => false],
            ['name' => 'Åland Islands', 'iso_code' => 'AX', 'iso3' => 'ALA', 'is_default' => false],
            ['name' => 'Albania', 'iso_code' => 'AL', 'iso3' => 'ALB', 'is_default' => false],
            ['name' => 'Andorra', 'iso_code' => 'AD', 'iso3' => 'AND', 'is_default' => false],
            ['name' => 'United Arab Emirates', 'iso_code' => 'AE', 'iso3' => 'ARE', 'is_default' => false],
            ['name' => 'Argentina', 'iso_code' => 'AR', 'iso3' => 'ARG', 'is_default' => true],
            ['name' => 'Armenia', 'iso_code' => 'AM', 'iso3' => 'ARM', 'is_default' => false],
            ['name' => 'American Samoa', 'iso_code' => 'AS', 'iso3' => 'ASM', 'is_default' => false],
            ['name' => 'Antarctica', 'iso_code' => 'AQ', 'iso3' => 'ATA', 'is_default' => false],
            ['name' => 'French Southern Territories', 'iso_code' => 'TF', 'iso3' => 'ATF', 'is_default' => false],
            ['name' => 'Antigua and Barbuda', 'iso_code' => 'AG', 'iso3' => 'ATG', 'is_default' => false],
            ['name' => 'Australia', 'iso_code' => 'AU', 'iso3' => 'AUS', 'is_default' => false],
            ['name' => 'Austria', 'iso_code' => 'AT', 'iso3' => 'AUT', 'is_default' => false],
            ['name' => 'Azerbaijan', 'iso_code' => 'AZ', 'iso3' => 'AZE', 'is_default' => false],
            ['name' => 'Burundi', 'iso_code' => 'BI', 'iso3' => 'BDI', 'is_default' => false],
            ['name' => 'Belgium', 'iso_code' => 'BE', 'iso3' => 'BEL', 'is_default' => false],
            ['name' => 'Benin', 'iso_code' => 'BJ', 'iso3' => 'BEN', 'is_default' => false],
            ['name' => 'Bonaire, Sint Eustatius and Saba', 'iso_code' => 'BQ', 'iso3' => 'BES', 'is_default' => false],
            ['name' => 'Burkina Faso', 'iso_code' => 'BF', 'iso3' => 'BFA', 'is_default' => false],
            ['name' => 'Bangladesh', 'iso_code' => 'BD', 'iso3' => 'BGD', 'is_default' => false],
            ['name' => 'Bulgaria', 'iso_code' => 'BG', 'iso3' => 'BGR', 'is_default' => false],
            ['name' => 'Bahrain', 'iso_code' => 'BH', 'iso3' => 'BHR', 'is_default' => false],
            ['name' => 'Bahamas', 'iso_code' => 'BS', 'iso3' => 'BHS', 'is_default' => false],
            ['name' => 'Bosnia and Herzegovina', 'iso_code' => 'BA', 'iso3' => 'BIH', 'is_default' => false],
            ['name' => 'Saint Barthélemy', 'iso_code' => 'BL', 'iso3' => 'BLM', 'is_default' => false],
            ['name' => 'Belarus', 'iso_code' => 'BY', 'iso3' => 'BLR', 'is_default' => false],
            ['name' => 'Belize', 'iso_code' => 'BZ', 'iso3' => 'BLZ', 'is_default' => false],
            ['name' => 'Bermuda', 'iso_code' => 'BM', 'iso3' => 'BMU', 'is_default' => false],
            ['name' => 'Bolivia, Plurinational State of', 'iso_code' => 'BO', 'iso3' => 'BOL', 'is_default' => false],
            ['name' => 'Brazil', 'iso_code' => 'BR', 'iso3' => 'BRA', 'is_default' => false],
            ['name' => 'Barbados', 'iso_code' => 'BB', 'iso3' => 'BRB', 'is_default' => false],
            ['name' => 'Brunei Darussalam', 'iso_code' => 'BN', 'iso3' => 'BRN', 'is_default' => false],
            ['name' => 'Bhutan', 'iso_code' => 'BT', 'iso3' => 'BTN', 'is_default' => false],
            ['name' => 'Bouvet Island', 'iso_code' => 'BV', 'iso3' => 'BVT', 'is_default' => false],
            ['name' => 'Botswana', 'iso_code' => 'BW', 'iso3' => 'BWA', 'is_default' => false],
            ['name' => 'Central African Republic', 'iso_code' => 'CF', 'iso3' => 'CAF', 'is_default' => false],
            ['name' => 'Canada', 'iso_code' => 'CA', 'iso3' => 'CAN', 'is_default' => false],
            ['name' => 'Cocos (Keeling) Islands', 'iso_code' => 'CC', 'iso3' => 'CCK', 'is_default' => false],
            ['name' => 'Switzerland', 'iso_code' => 'CH', 'iso3' => 'CHE', 'is_default' => false],
            ['name' => 'Chile', 'iso_code' => 'CL', 'iso3' => 'CHL', 'is_default' => false],
            ['name' => 'China', 'iso_code' => 'CN', 'iso3' => 'CHN', 'is_default' => false],
            ['name' => 'Côte d\'Ivoire', 'iso_code' => 'CI', 'iso3' => 'CIV', 'is_default' => false],
            ['name' => 'Cameroon', 'iso_code' => 'CM', 'iso3' => 'CMR', 'is_default' => false],
            ['name' => 'Congo, The Democratic Republic of the', 'iso_code' => 'CD', 'iso3' => 'COD', 'is_default' => false],
            ['name' => 'Congo', 'iso_code' => 'CG', 'iso3' => 'COG', 'is_default' => false],
            ['name' => 'Cook Islands', 'iso_code' => 'CK', 'iso3' => 'COK', 'is_default' => false],
            ['name' => 'Colombia', 'iso_code' => 'CO', 'iso3' => 'COL', 'is_default' => false],
            ['name' => 'Comoros', 'iso_code' => 'KM', 'iso3' => 'COM', 'is_default' => false],
            ['name' => 'Cabo Verde', 'iso_code' => 'CV', 'iso3' => 'CPV', 'is_default' => false],
            ['name' => 'Costa Rica', 'iso_code' => 'CR', 'iso3' => 'CRI', 'is_default' => false],
            ['name' => 'Cuba', 'iso_code' => 'CU', 'iso3' => 'CUB', 'is_default' => false],
            ['name' => 'Curaçao', 'iso_code' => 'CW', 'iso3' => 'CUW', 'is_default' => false],
            ['name' => 'Christmas Island', 'iso_code' => 'CX', 'iso3' => 'CXR', 'is_default' => false],
            ['name' => 'Cayman Islands', 'iso_code' => 'KY', 'iso3' => 'CYM', 'is_default' => false],
            ['name' => 'Cyprus', 'iso_code' => 'CY', 'iso3' => 'CYP', 'is_default' => false],
            ['name' => 'Czechia', 'iso_code' => 'CZ', 'iso3' => 'CZE', 'is_default' => false],
            ['name' => 'Germany', 'iso_code' => 'DE', 'iso3' => 'DEU', 'is_default' => false],
            ['name' => 'Djibouti', 'iso_code' => 'DJ', 'iso3' => 'DJI', 'is_default' => false],
            ['name' => 'Dominica', 'iso_code' => 'DM', 'iso3' => 'DMA', 'is_default' => false],
            ['name' => 'Denmark', 'iso_code' => 'DK', 'iso3' => 'DNK', 'is_default' => false],
            ['name' => 'Dominican Republic', 'iso_code' => 'DO', 'iso3' => 'DOM', 'is_default' => false],
            ['name' => 'Algeria', 'iso_code' => 'DZ', 'iso3' => 'DZA', 'is_default' => false],
            ['name' => 'Ecuador', 'iso_code' => 'EC', 'iso3' => 'ECU', 'is_default' => false],
            ['name' => 'Egypt', 'iso_code' => 'EG', 'iso3' => 'EGY', 'is_default' => false],
            ['name' => 'Eritrea', 'iso_code' => 'ER', 'iso3' => 'ERI', 'is_default' => false],
            ['name' => 'Western Sahara', 'iso_code' => 'EH', 'iso3' => 'ESH', 'is_default' => false],
            ['name' => 'Spain', 'iso_code' => 'ES', 'iso3' => 'ESP', 'is_default' => false],
            ['name' => 'Estonia', 'iso_code' => 'EE', 'iso3' => 'EST', 'is_default' => false],
            ['name' => 'Ethiopia', 'iso_code' => 'ET', 'iso3' => 'ETH', 'is_default' => false],
            ['name' => 'Finland', 'iso_code' => 'FI', 'iso3' => 'FIN', 'is_default' => false],
            ['name' => 'Fiji', 'iso_code' => 'FJ', 'iso3' => 'FJI', 'is_default' => false],
            ['name' => 'Falkland Islands (Malvinas)', 'iso_code' => 'FK', 'iso3' => 'FLK', 'is_default' => false],
            ['name' => 'France', 'iso_code' => 'FR', 'iso3' => 'FRA', 'is_default' => false],
            ['name' => 'Faroe Islands', 'iso_code' => 'FO', 'iso3' => 'FRO', 'is_default' => false],
            ['name' => 'Micronesia, Federated States of', 'iso_code' => 'FM', 'iso3' => 'FSM', 'is_default' => false],
            ['name' => 'Gabon', 'iso_code' => 'GA', 'iso3' => 'GAB', 'is_default' => false],
            ['name' => 'United Kingdom', 'iso_code' => 'GB', 'iso3' => 'GBR', 'is_default' => false],
            ['name' => 'Georgia', 'iso_code' => 'GE', 'iso3' => 'GEO', 'is_default' => false],
            ['name' => 'Guernsey', 'iso_code' => 'GG', 'iso3' => 'GGY', 'is_default' => false],
            ['name' => 'Ghana', 'iso_code' => 'GH', 'iso3' => 'GHA', 'is_default' => false],
            ['name' => 'Gibraltar', 'iso_code' => 'GI', 'iso3' => 'GIB', 'is_default' => false],
            ['name' => 'Guinea', 'iso_code' => 'GN', 'iso3' => 'GIN', 'is_default' => false],
            ['name' => 'Guadeloupe', 'iso_code' => 'GP', 'iso3' => 'GLP', 'is_default' => false],
            ['name' => 'Gambia', 'iso_code' => 'GM', 'iso3' => 'GMB', 'is_default' => false],
            ['name' => 'Guinea-Bissau', 'iso_code' => 'GW', 'iso3' => 'GNB', 'is_default' => false],
            ['name' => 'Equatorial Guinea', 'iso_code' => 'GQ', 'iso3' => 'GNQ', 'is_default' => false],
            ['name' => 'Greece', 'iso_code' => 'GR', 'iso3' => 'GRC', 'is_default' => false],
            ['name' => 'Grenada', 'iso_code' => 'GD', 'iso3' => 'GRD', 'is_default' => false],
            ['name' => 'Greenland', 'iso_code' => 'GL', 'iso3' => 'GRL', 'is_default' => false],
            ['name' => 'Guatemala', 'iso_code' => 'GT', 'iso3' => 'GTM', 'is_default' => false],
            ['name' => 'French Guiana', 'iso_code' => 'GF', 'iso3' => 'GUF', 'is_default' => false],
            ['name' => 'Guam', 'iso_code' => 'GU', 'iso3' => 'GUM', 'is_default' => false],
            ['name' => 'Guyana', 'iso_code' => 'GY', 'iso3' => 'GUY', 'is_default' => false],
            ['name' => 'Hong Kong', 'iso_code' => 'HK', 'iso3' => 'HKG', 'is_default' => false],
            ['name' => 'Heard Island and McDonald Islands', 'iso_code' => 'HM', 'iso3' => 'HMD', 'is_default' => false],
            ['name' => 'Honduras', 'iso_code' => 'HN', 'iso3' => 'HND', 'is_default' => false],
            ['name' => 'Croatia', 'iso_code' => 'HR', 'iso3' => 'HRV', 'is_default' => false],
            ['name' => 'Haiti', 'iso_code' => 'HT', 'iso3' => 'HTI', 'is_default' => false],
            ['name' => 'Hungary', 'iso_code' => 'HU', 'iso3' => 'HUN', 'is_default' => false],
            ['name' => 'Indonesia', 'iso_code' => 'ID', 'iso3' => 'IDN', 'is_default' => false],
            ['name' => 'Isle of Man', 'iso_code' => 'IM', 'iso3' => 'IMN', 'is_default' => false],
            ['name' => 'India', 'iso_code' => 'IN', 'iso3' => 'IND', 'is_default' => false],
            ['name' => 'British Indian Ocean Territory', 'iso_code' => 'IO', 'iso3' => 'IOT', 'is_default' => false],
            ['name' => 'Ireland', 'iso_code' => 'IE', 'iso3' => 'IRL', 'is_default' => false],
            ['name' => 'Iran, Islamic Republic of', 'iso_code' => 'IR', 'iso3' => 'IRN', 'is_default' => false],
            ['name' => 'Iraq', 'iso_code' => 'IQ', 'iso3' => 'IRQ', 'is_default' => false],
            ['name' => 'Iceland', 'iso_code' => 'IS', 'iso3' => 'ISL', 'is_default' => false],
            ['name' => 'Israel', 'iso_code' => 'IL', 'iso3' => 'ISR', 'is_default' => false],
            ['name' => 'Italy', 'iso_code' => 'IT', 'iso3' => 'ITA', 'is_default' => false],
            ['name' => 'Jamaica', 'iso_code' => 'JM', 'iso3' => 'JAM', 'is_default' => false],
            ['name' => 'Jersey', 'iso_code' => 'JE', 'iso3' => 'JEY', 'is_default' => false],
            ['name' => 'Jordan', 'iso_code' => 'JO', 'iso3' => 'JOR', 'is_default' => false],
            ['name' => 'Japan', 'iso_code' => 'JP', 'iso3' => 'JPN', 'is_default' => false],
            ['name' => 'Kazakhstan', 'iso_code' => 'KZ', 'iso3' => 'KAZ', 'is_default' => false],
            ['name' => 'Kenya', 'iso_code' => 'KE', 'iso3' => 'KEN', 'is_default' => false],
            ['name' => 'Kyrgyzstan', 'iso_code' => 'KG', 'iso3' => 'KGZ', 'is_default' => false],
            ['name' => 'Cambodia', 'iso_code' => 'KH', 'iso3' => 'KHM', 'is_default' => false],
            ['name' => 'Kiribati', 'iso_code' => 'KI', 'iso3' => 'KIR', 'is_default' => false],
            ['name' => 'Saint Kitts and Nevis', 'iso_code' => 'KN', 'iso3' => 'KNA', 'is_default' => false],
            ['name' => 'Korea, Republic of', 'iso_code' => 'KR', 'iso3' => 'KOR', 'is_default' => false],
            ['name' => 'Kuwait', 'iso_code' => 'KW', 'iso3' => 'KWT', 'is_default' => false],
            ['name' => 'Lao People\'s Democratic Republic', 'iso_code' => 'LA', 'iso3' => 'LAO', 'is_default' => false],
            ['name' => 'Lebanon', 'iso_code' => 'LB', 'iso3' => 'LBN', 'is_default' => false],
            ['name' => 'Liberia', 'iso_code' => 'LR', 'iso3' => 'LBR', 'is_default' => false],
            ['name' => 'Libya', 'iso_code' => 'LY', 'iso3' => 'LBY', 'is_default' => false],
            ['name' => 'Saint Lucia', 'iso_code' => 'LC', 'iso3' => 'LCA', 'is_default' => false],
            ['name' => 'Liechtenstein', 'iso_code' => 'LI', 'iso3' => 'LIE', 'is_default' => false],
            ['name' => 'Sri Lanka', 'iso_code' => 'LK', 'iso3' => 'LKA', 'is_default' => false],
            ['name' => 'Lesotho', 'iso_code' => 'LS', 'iso3' => 'LSO', 'is_default' => false],
            ['name' => 'Lithuania', 'iso_code' => 'LT', 'iso3' => 'LTU', 'is_default' => false],
            ['name' => 'Luxembourg', 'iso_code' => 'LU', 'iso3' => 'LUX', 'is_default' => false],
            ['name' => 'Latvia', 'iso_code' => 'LV', 'iso3' => 'LVA', 'is_default' => false],
            ['name' => 'Macao', 'iso_code' => 'MO', 'iso3' => 'MAC', 'is_default' => false],
            ['name' => 'Saint Martin (French part)', 'iso_code' => 'MF', 'iso3' => 'MAF', 'is_default' => false],
            ['name' => 'Morocco', 'iso_code' => 'MA', 'iso3' => 'MAR', 'is_default' => false],
            ['name' => 'Monaco', 'iso_code' => 'MC', 'iso3' => 'MCO', 'is_default' => false],
            ['name' => 'Moldova, Republic of', 'iso_code' => 'MD', 'iso3' => 'MDA', 'is_default' => false],
            ['name' => 'Madagascar', 'iso_code' => 'MG', 'iso3' => 'MDG', 'is_default' => false],
            ['name' => 'Maldives', 'iso_code' => 'MV', 'iso3' => 'MDV', 'is_default' => false],
            ['name' => 'Mexico', 'iso_code' => 'MX', 'iso3' => 'MEX', 'is_default' => false],
            ['name' => 'Marshall Islands', 'iso_code' => 'MH', 'iso3' => 'MHL', 'is_default' => false],
            ['name' => 'North Macedonia', 'iso_code' => 'MK', 'iso3' => 'MKD', 'is_default' => false],
            ['name' => 'Mali', 'iso_code' => 'ML', 'iso3' => 'MLI', 'is_default' => false],
            ['name' => 'Malta', 'iso_code' => 'MT', 'iso3' => 'MLT', 'is_default' => false],
            ['name' => 'Myanmar', 'iso_code' => 'MM', 'iso3' => 'MMR', 'is_default' => false],
            ['name' => 'Montenegro', 'iso_code' => 'ME', 'iso3' => 'MNE', 'is_default' => false],
            ['name' => 'Mongolia', 'iso_code' => 'MN', 'iso3' => 'MNG', 'is_default' => false],
            ['name' => 'Northern Mariana Islands', 'iso_code' => 'MP', 'iso3' => 'MNP', 'is_default' => false],
            ['name' => 'Mozambique', 'iso_code' => 'MZ', 'iso3' => 'MOZ', 'is_default' => false],
            ['name' => 'Mauritania', 'iso_code' => 'MR', 'iso3' => 'MRT', 'is_default' => false],
            ['name' => 'Montserrat', 'iso_code' => 'MS', 'iso3' => 'MSR', 'is_default' => false],
            ['name' => 'Martinique', 'iso_code' => 'MQ', 'iso3' => 'MTQ', 'is_default' => false],
            ['name' => 'Mauritius', 'iso_code' => 'MU', 'iso3' => 'MUS', 'is_default' => false],
            ['name' => 'Malawi', 'iso_code' => 'MW', 'iso3' => 'MWI', 'is_default' => false],
            ['name' => 'Malaysia', 'iso_code' => 'MY', 'iso3' => 'MYS', 'is_default' => false],
            ['name' => 'Mayotte', 'iso_code' => 'YT', 'iso3' => 'MYT', 'is_default' => false],
            ['name' => 'Namibia', 'iso_code' => 'NA', 'iso3' => 'NAM', 'is_default' => false],
            ['name' => 'New Caledonia', 'iso_code' => 'NC', 'iso3' => 'NCL', 'is_default' => false],
            ['name' => 'Niger', 'iso_code' => 'NE', 'iso3' => 'NER', 'is_default' => false],
            ['name' => 'Norfolk Island', 'iso_code' => 'NF', 'iso3' => 'NFK', 'is_default' => false],
            ['name' => 'Nigeria', 'iso_code' => 'NG', 'iso3' => 'NGA', 'is_default' => false],
            ['name' => 'Nicaragua', 'iso_code' => 'NI', 'iso3' => 'NIC', 'is_default' => false],
            ['name' => 'Niue', 'iso_code' => 'NU', 'iso3' => 'NIU', 'is_default' => false],
            ['name' => 'Netherlands', 'iso_code' => 'NL', 'iso3' => 'NLD', 'is_default' => false],
            ['name' => 'Norway', 'iso_code' => 'NO', 'iso3' => 'NOR', 'is_default' => false],
            ['name' => 'Nepal', 'iso_code' => 'NP', 'iso3' => 'NPL', 'is_default' => false],
            ['name' => 'Nauru', 'iso_code' => 'NR', 'iso3' => 'NRU', 'is_default' => false],
            ['name' => 'New Zealand', 'iso_code' => 'NZ', 'iso3' => 'NZL', 'is_default' => false],
            ['name' => 'Oman', 'iso_code' => 'OM', 'iso3' => 'OMN', 'is_default' => false],
            ['name' => 'Pakistan', 'iso_code' => 'PK', 'iso3' => 'PAK', 'is_default' => false],
            ['name' => 'Panama', 'iso_code' => 'PA', 'iso3' => 'PAN', 'is_default' => false],
            ['name' => 'Pitcairn', 'iso_code' => 'PN', 'iso3' => 'PCN', 'is_default' => false],
            ['name' => 'Peru', 'iso_code' => 'PE', 'iso3' => 'PER', 'is_default' => false],
            ['name' => 'Philippines', 'iso_code' => 'PH', 'iso3' => 'PHL', 'is_default' => false],
            ['name' => 'Palau', 'iso_code' => 'PW', 'iso3' => 'PLW', 'is_default' => false],
            ['name' => 'Papua New Guinea', 'iso_code' => 'PG', 'iso3' => 'PNG', 'is_default' => false],
            ['name' => 'Poland', 'iso_code' => 'PL', 'iso3' => 'POL', 'is_default' => false],
            ['name' => 'Puerto Rico', 'iso_code' => 'PR', 'iso3' => 'PRI', 'is_default' => false],
            ['name' => 'Korea, Democratic People\'s Republic of', 'iso_code' => 'KP', 'iso3' => 'PRK', 'is_default' => false],
            ['name' => 'Portugal', 'iso_code' => 'PT', 'iso3' => 'PRT', 'is_default' => false],
            ['name' => 'Paraguay', 'iso_code' => 'PY', 'iso3' => 'PRY', 'is_default' => false],
            ['name' => 'Palestine, State of', 'iso_code' => 'PS', 'iso3' => 'PSE', 'is_default' => false],
            ['name' => 'French Polynesia', 'iso_code' => 'PF', 'iso3' => 'PYF', 'is_default' => false],
            ['name' => 'Qatar', 'iso_code' => 'QA', 'iso3' => 'QAT', 'is_default' => false],
            ['name' => 'Réunion', 'iso_code' => 'RE', 'iso3' => 'REU', 'is_default' => false],
            ['name' => 'Romania', 'iso_code' => 'RO', 'iso3' => 'ROU', 'is_default' => false],
            ['name' => 'Russian Federation', 'iso_code' => 'RU', 'iso3' => 'RUS', 'is_default' => false],
            ['name' => 'Rwanda', 'iso_code' => 'RW', 'iso3' => 'RWA', 'is_default' => false],
            ['name' => 'Saudi Arabia', 'iso_code' => 'SA', 'iso3' => 'SAU', 'is_default' => false],
            ['name' => 'Sudan', 'iso_code' => 'SD', 'iso3' => 'SDN', 'is_default' => false],
            ['name' => 'Senegal', 'iso_code' => 'SN', 'iso3' => 'SEN', 'is_default' => false],
            ['name' => 'Singapore', 'iso_code' => 'SG', 'iso3' => 'SGP', 'is_default' => false],
            ['name' => 'South Georgia and the South Sandwich Islands', 'iso_code' => 'GS', 'iso3' => 'SGS', 'is_default' => false],
            ['name' => 'Saint Helena, Ascension and Tristan da Cunha', 'iso_code' => 'SH', 'iso3' => 'SHN', 'is_default' => false],
            ['name' => 'Svalbard and Jan Mayen', 'iso_code' => 'SJ', 'iso3' => 'SJM', 'is_default' => false],
            ['name' => 'Solomon Islands', 'iso_code' => 'SB', 'iso3' => 'SLB', 'is_default' => false],
            ['name' => 'Sierra Leone', 'iso_code' => 'SL', 'iso3' => 'SLE', 'is_default' => false],
            ['name' => 'El Salvador', 'iso_code' => 'SV', 'iso3' => 'SLV', 'is_default' => false],
            ['name' => 'San Marino', 'iso_code' => 'SM', 'iso3' => 'SMR', 'is_default' => false],
            ['name' => 'Somalia', 'iso_code' => 'SO', 'iso3' => 'SOM', 'is_default' => false],
            ['name' => 'Saint Pierre and Miquelon', 'iso_code' => 'PM', 'iso3' => 'SPM', 'is_default' => false],
            ['name' => 'Serbia', 'iso_code' => 'RS', 'iso3' => 'SRB', 'is_default' => false],
            ['name' => 'South Sudan', 'iso_code' => 'SS', 'iso3' => 'SSD', 'is_default' => false],
            ['name' => 'Sao Tome and Principe', 'iso_code' => 'ST', 'iso3' => 'STP', 'is_default' => false],
            ['name' => 'Suriname', 'iso_code' => 'SR', 'iso3' => 'SUR', 'is_default' => false],
            ['name' => 'Slovakia', 'iso_code' => 'SK', 'iso3' => 'SVK', 'is_default' => false],
            ['name' => 'Slovenia', 'iso_code' => 'SI', 'iso3' => 'SVN', 'is_default' => false],
            ['name' => 'Sweden', 'iso_code' => 'SE', 'iso3' => 'SWE', 'is_default' => false],
            ['name' => 'Eswatini', 'iso_code' => 'SZ', 'iso3' => 'SWZ', 'is_default' => false],
            ['name' => 'Sint Maarten (Dutch part)', 'iso_code' => 'SX', 'iso3' => 'SXM', 'is_default' => false],
            ['name' => 'Seychelles', 'iso_code' => 'SC', 'iso3' => 'SYC', 'is_default' => false],
            ['name' => 'Syrian Arab Republic', 'iso_code' => 'SY', 'iso3' => 'SYR', 'is_default' => false],
            ['name' => 'Turks and Caicos Islands', 'iso_code' => 'TC', 'iso3' => 'TCA', 'is_default' => false],
            ['name' => 'Chad', 'iso_code' => 'TD', 'iso3' => 'TCD', 'is_default' => false],
            ['name' => 'Togo', 'iso_code' => 'TG', 'iso3' => 'TGO', 'is_default' => false],
            ['name' => 'Thailand', 'iso_code' => 'TH', 'iso3' => 'THA', 'is_default' => false],
            ['name' => 'Tajikistan', 'iso_code' => 'TJ', 'iso3' => 'TJK', 'is_default' => false],
            ['name' => 'Tokelau', 'iso_code' => 'TK', 'iso3' => 'TKL', 'is_default' => false],
            ['name' => 'Turkmenistan', 'iso_code' => 'TM', 'iso3' => 'TKM', 'is_default' => false],
            ['name' => 'Timor-Leste', 'iso_code' => 'TL', 'iso3' => 'TLS', 'is_default' => false],
            ['name' => 'Tonga', 'iso_code' => 'TO', 'iso3' => 'TON', 'is_default' => false],
            ['name' => 'Trinidad and Tobago', 'iso_code' => 'TT', 'iso3' => 'TTO', 'is_default' => false],
            ['name' => 'Tunisia', 'iso_code' => 'TN', 'iso3' => 'TUN', 'is_default' => false],
            ['name' => 'Turkey', 'iso_code' => 'TR', 'iso3' => 'TUR', 'is_default' => false],
            ['name' => 'Tuvalu', 'iso_code' => 'TV', 'iso3' => 'TUV', 'is_default' => false],
            ['name' => 'Taiwan, Province of China', 'iso_code' => 'TW', 'iso3' => 'TWN', 'is_default' => false],
            ['name' => 'Tanzania, United Republic of', 'iso_code' => 'TZ', 'iso3' => 'TZA', 'is_default' => false],
            ['name' => 'Uganda', 'iso_code' => 'UG', 'iso3' => 'UGA', 'is_default' => false],
            ['name' => 'Ukraine', 'iso_code' => 'UA', 'iso3' => 'UKR', 'is_default' => false],
            ['name' => 'United States Minor Outlying Islands', 'iso_code' => 'UM', 'iso3' => 'UMI', 'is_default' => false],
            ['name' => 'Uruguay', 'iso_code' => 'UY', 'iso3' => 'URY', 'is_default' => false],
            ['name' => 'United States', 'iso_code' => 'US', 'iso3' => 'USA', 'is_default' => false],
            ['name' => 'Uzbekistan', 'iso_code' => 'UZ', 'iso3' => 'UZB', 'is_default' => false],
            ['name' => 'Holy See (Vatican City State)', 'iso_code' => 'VA', 'iso3' => 'VAT', 'is_default' => false],
            ['name' => 'Saint Vincent and the Grenadines', 'iso_code' => 'VC', 'iso3' => 'VCT', 'is_default' => false],
            ['name' => 'Venezuela, Bolivarian Republic of', 'iso_code' => 'VE', 'iso3' => 'VEN', 'is_default' => false],
            ['name' => 'Virgin Islands, British', 'iso_code' => 'VG', 'iso3' => 'VGB', 'is_default' => false],
            ['name' => 'Virgin Islands, U.S.', 'iso_code' => 'VI', 'iso3' => 'VIR', 'is_default' => false],
            ['name' => 'Viet Nam', 'iso_code' => 'VN', 'iso3' => 'VNM', 'is_default' => false],
            ['name' => 'Vanuatu', 'iso_code' => 'VU', 'iso3' => 'VUT', 'is_default' => false],
            ['name' => 'Wallis and Futuna', 'iso_code' => 'WF', 'iso3' => 'WLF', 'is_default' => false],
            ['name' => 'Samoa', 'iso_code' => 'WS', 'iso3' => 'WSM', 'is_default' => false],
            ['name' => 'Yemen', 'iso_code' => 'YE', 'iso3' => 'YEM', 'is_default' => false],
            ['name' => 'South Africa', 'iso_code' => 'ZA', 'iso3' => 'ZAF', 'is_default' => false],
            ['name' => 'Zambia', 'iso_code' => 'ZM', 'iso3' => 'ZMB', 'is_default' => false],
            ['name' => 'Zimbabwe', 'iso_code' => 'ZW', 'iso3' => 'ZWE', 'is_default' => false],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['name' => $country['name']],
                [
                    'iso_code' => $country['iso_code'],
                    'iso3' => $country['iso3'],
                    'is_default' => $country['is_default'],
                ]
            );
        }
    }
}
