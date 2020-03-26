# Insert csv data about covid-19 from the github repository of Johns Hopkins University to MYSQL table

PHP scripts that grab data from [JHU github repository](https://github.com/CSSEGISandData) JHU github repository, normalize available csv tables and insert them in a mysql table

The scripts read and aggregate daily reports tables in a single mysql table 

Import in mysql table with the aim of facilitating the development of web based applications based on HTML, CSS, Javascript and PHP.

The normalization process is due to a lack of standardization in the format of csv tables, in particular the different date formats, the use of special characters not escaped, strings not double quoted, field names and the position of fields.

Scripts try to avoid as much as possible manual work.

Two routines are particularly useful:

populate()
grab data from the beginning up to March, 21, 2020. After that date the structure of csv table is different therefore fires mysql errors

update()
grab data from the March, 22, 2020. Modifies data structure in order to make it compatible with previous csv data.

Original data structure and formats are unpredictable therefore new routines could be created for standardization purposes.

This is a live development, the script is subject to modifications.

Main issues are:

data format:

from the beginnig to February 1, 2020
M/D/YYYY hh:mm

from February 2, 2020
YYYY-MM-DDThh:mm:ss

from March 22, 2020
M/DD/YY hh:mm

from March 23, 2020
YYYY-MM-DD hh:mm:ss

Field names and position:

from the beginning

Province/State
Country/Region
Last Update
Confirmed
Deaths
Recovered
Latitude
Longitude

from March 22, 2020

FIPS
Admin2
Province_State
Country_Region
Last_Update
Lat
Long_
Confirmed
Deaths
Recovered
Active
Combined_Key









