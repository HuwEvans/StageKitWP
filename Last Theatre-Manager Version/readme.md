# Theatre Manager Wordpress Plugin

## Packaging Command

From the repository root, run:

```powershell
powershell -ExecutionPolicy Bypass -File .\package.ps1
```

This creates a versioned zip file using the plugin header version, for example `Theatre-Manager-3.1.zip`.
Inside the zip, the plugin is packaged under the standard lowercase folder name `theatre-manager` for WordPress compatibility.
You can override the version explicitly:

```powershell
powershell -ExecutionPolicy Bypass -File .\package.ps1 -Version 2.8
```

This plugin is designed to work with Wordpress and wsa developed to help theatre groups manage important objects
on their websites.  It was designed after serveral years of maintaining a website for this purpose, and combines
simpliying the management of common changes that are required, and making it simple for non-technical people to 
update the information.  Once it is up and running, it should be very sraight forward to make changes and have 
these reflected on the appropriate pages with very little technical know how.

The plugin has 2 main aspects.  The Custom Post Types (CPTs) and the shortcodes to go along with the CPTs.  This
combination of tools should allow someone to easily inplement these on a wordpress website, and provide a modcum
of control over how the elements are displayed.

## The Custom Post Types
### Advertisers
	This CPT allows for entering advertisers that have paid the group to help prmot there business.  It will keep 
	track of the name, logo, banner and website of each advertiser.  There is also a flag to designate an advertiser
	as a restaurant.  This was done to allow for the ability to create a 'Diner's Guide'.  Helping to promote
	local restaurants that patrons might what to frequent before or after a show.  It allows the advertisers to 
	supply a link to a promotion that they can offer to people who get to them via advertising with the group.
### Board Members
	This CPT tracks information about the board members or creative team for the group.  It has the names and 
	positions of each board member, and allows for a picture to be added for each board member.
### Contributors
	This CPT track infomation about donors to the group and provides a means of acknowlegding them on the website. 
	It provides an easy way to manage the donors and see them in one place.
### Sponsors
	This CPT provides a means of tracking sponsors to the group.  This will allow for generating a of people and 
	organizations that have taken part in the sponsorship program with the group.
### Testimonials
	This CPT allows for tracking of testimonials that have been provided by indivuals who have seen the shows and
	have gone out of there way to provide feedback.
### Seasons -> Shows -> Casts
	These CPTs are probable the most ussful of all the CPTs.  It provides a means to easily track the information 
	about a season, including the shows and cast members for each show.  When maintaining this information for the
	group that I was a part of, it was the area that required the most updating, and had several components that 
	were difficult to maintain.  Providing these CPTs, I wanted to provide an easy way to link all of this data 
	together for easy display, and provide a simple way see and modify the data.

## Display Options
This section of the plugin admin area will help with customizing the way that the shortcodes display the CPTs. 
Primarily, you will be able to control the background color, the text color, the border color and width, whether
or not there are rounded corners, and then some other specifics to the individual CPT.  Please note that not all
settings apply to all shortcode display types.  For instance the slider types typically do not use any cusomizations
from the display options.

## The Shortcodes
### Advertisers
#### [tm_advertisers]
This short code will help you display the advertisers CPTs in multiple ways.  As part of our advertising process
we included for the price of advertising inclusion in our diners guide for restaurants.  This is the one filter 
that I have added to the advertiser shortcode to simply the creation of the diners guide. Images will be linked 
with the website provided in the CPT.

Options:
	view:  slider or grid (default is grid)
	category: restaurant (default is all)
	image_type: banner or logo (default is banner) - Only used for slider view
	columns:  1-6 (default is 3) - Only used for grid view
	
Example:
	[tm_advertisers view="slider" category="restaurant"]
	This will show only the restaurants in a slider using the banner image
	[tm_advertisers]
	This will use the defaults, and show a grid of all advertisers in a 3 column format.

### Board Members
#### [tm_board_members]
This short code will display the CPT for board members on any page or post that it is placed on.  It will show the
members in a grid with the defined number of columns.  The display options will help with determining how the members
are displayed.

Options:
	show_photos: true/false (default true)
	columns: 1-6 (default 3, configurable in the display options)
Example:
	[tm_board_members]
	Shows the board memebers in a 3 column grid with photos
	[tm_board_members show_photos="false" columns="4"]
	Shows the board members in a 4 column grid without photos.
	
### Contributors
#### [tm_contributors]
This shortcode will display the contributors CPT data in a tiered display.  There are no options available for this
shortcode, however some control over the display options is available in the admin panel as mentioned above.

Options:  None
	No options are available, but some control over how the items are displayed is available in the Display Options.
Example:
	[tm_contributors]
	Show the contributors in a tiered list.  Platinum one column, gold 2 column, silver 3 column, bronze 4 column.
	
### Sponsors
#### [tm_sponsors]
This is one of two shortcodes for this CPT.  This will show the sponsors in a tiered grid similar to the contributors.
The options will allow you to control which peices of information are included in the display cards, and additional
control of the display options is available in the admin menu.

Options:
        show_name: true/false (default true) toggle the display of individual names
        show_company: true/false (default true) toggle the display of company names
        show_logo: true/false (default true) if set to false will show banner images
        show_website: true/false (default true) show text of the website - image links will no be affected
				
Example:
	[tm_sponsors]
	This will use the defaults and show all fields as part of the display.  This will show the tiered grid version of
	the logos provided, linked to the website provided, and show the individuals name, the company and the website.  The
	tiered display will be Platinum one column, gold 2 column, silver 3 column, bronze 4 column.
	[tm_sponsors show_name="true" show_company="false" show_logo="true" show_website="false"]
	This will show the tiered display, but will only show the individuals name, and the logo (linked to the website).

#### [tm_sponsor_slider]
This shortcode is designed to provide a slider of the banners supplied for all sponsors.  This was meant to provide a tool
to put a vertical banner

Options:	None
	No Options are available and no display options will affect this shortcode.  This shortcode is designed to display
	banners in a slider.
	
Example:
	[tm_sponsor_slider]
	Will show the banner images in a slider.
	
### Testimonials
#### [tm_testimonials]
This shortcode will display a slider of testimonials.  The only options for this shortcode will be in the display options 
in the admin panel.

Options:	None
	No options are avialable to configure the shortcode itself, howver there are many display options in the admin panel 
	that will allow for customizations of the testimonial slider.

Example:
	[tm_testimonials]
	This will show the testimonial slider with the display options customizations
	
### Seasons -> Shows -> Casts
#### [tm_show_cast]
This shortcode will display the cast associated with a specific show.  It will show all fields and will not be contain a
lot of formatting.  It will include the pictures and all fields, but no show information.

Options:
	show_id: <number> (required) id of show to display.
	
Example:
	[tm_show_cast show_id="177"]
	This displays information pertining to show with post ID 177.
	
#### [tm_season_banner]
This shortcode will show the Social Media banner for the season specified.

Options:
	season_id: <number>  (required)  ID of season to show information about.

Example:
	[tm_season_banner season_id="177"]

#### [tm_season_cast]
This shortcode will show all of the shows for a season, along with the show details, and include the cast associated with
with the show.  There are options on how the data will be displayed that can be used to modify the shortcode behaviour.  The
Shows will be displayed sorted by time slot.  (Fall, Winter, Spring)

Options:
        season_id: <number> (required)  Use the ID number of the season you want to display
        show_cast_images:  true/false  (default true) Use to toggle cast pictures
        cast_layout: grid/list (default grid) Use to determine the layout of the cast

Example:
	[tm_season_cast seasonid="177" show_cast_images=false cast_layout="grid"]
	This example will display the shows for season with the ID of 177.  It will show the details of any field with data in
	it and will show the cast in a grid with no pictures.

#### [tm_season_images]
This shortcode will display all three images for the given season.  It will put the Social Media banner on the top, and the
3-up (or flyer images) underneath next to each other.

Options:
	season_id: <number> (required)  Use this to determine which season details will be shown
	
Example:
	[tm_season_images seasonid="177"]
	This example will show the season images for season with the ID 177.
	
#### [tm_season_shows]

Options:	
	season_id:  <number>   Use this to display a single season.  Default is to show all seasons.

Example:
	[tm_season_shows]
	This example will display each season and the associated show summaries in a 3 column grid.
	[tm_season_shows season_id="177"]
	This example will display the shows for the seaosn with ID 177 in a trhee olumn grid.

#### [tm_cast]
This shortcode will display all cast posts.  Very little formatting is done, and there are currenly no filtering options for
this shortcode 

Options:
	exclude: 'actor_name', 'picture', 'show'

Example:
	[tm_cast exclude="actor_name"]
	By default, all fields are shown. Use 'exclude' to hide specific fields.  This sample will hide the actor_name field

#### [tm_seasons]
This shortcode shows all season data unformatted.  The exclude options allows you to hide fields.

Options:
	exclude: 'image_front', 'image_back', 'social_banner', 'start_date', 'end_date'

Example:
	Usage: [tm_seasons exclude="start_date,end_date"]
	By default, all fields are shown. Use 'exclude' to hide specific fields.  This sample will hide the start and end dates

#### [tm_shows]
This shortcode will show the details of all shows.  Fields can be excluded by using the exclude option

Options:
	exclude: 'author', 'sub_authors', 'synopsis', 'genre', 'director', 'associate_director', 'time_slot', 'show_dates'

Example:
	[tm_shows exclude="genre,director"]
	By default, all fields are shown. Use 'exclude' to hide specific fields.  This sample will hide the director and genre fields
