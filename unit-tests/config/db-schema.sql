DROP TABLE IF EXISTS person;
CREATE TABLE person (
	id int unsigned auto_increment, 
	name varchar(50) NOT NULL default '',
	age float(3) default 0.00,
	PRIMARY KEY (id)
);
DROP TABLE IF EXISTS address;
CREATE TABLE address (
	postcode varchar(10),
	houseno varchar(50),
	street varchar(100) NOT NULL default '',
	city varchar(100) NOT NULL default '',
	province varchar(100) NOT NULL default '',
	PRIMARY KEY (postcode, houseno)
);
DROP TABLE IF EXISTS person_address;
CREATE TABLE person_address (
	id int unsigned auto_increment,
	person_id int unsigned NOT NULL default 0,
	address_id int unsigned NOT NULL default 0,
	PRIMARY KEY (id)
);
DROP TABLE IF EXISTS account;
CREATE TABLE account (
	id int unsigned auto_increment,
	person_id int unsigned not null default 0,
	name varchar(100) not null default '',
	primary key(id)
);
DROP TABLE IF EXISTS friend;
CREATE TABLE friend (
	id int unsigned auto_increment, 
	person_a_id int unsigned NOT NULL default 0,
	person_b_id int unsigned NOT NULL default 0,
	PRIMARY KEY (id)
);
DROP TABLE IF EXISTS person_alias;
CREATE TABLE person_alias (
	id int unsigned auto_increment,
	person_id int unsigned not null default 0,
	person_alias varchar(100) not null default '',
	primary key(id)
);
DROP TABLE IF EXISTS library;
CREATE TABLE library (
	id int unsigned auto_increment,
	name varchar(100) not null default '',
	primary key(id)
);
DROP TABLE IF EXISTS book;
CREATE TABLE book (
	id int unsigned auto_increment,
	library_id int unsigned not null default 0,
	title varchar(100) not null default '',
	primary key(id)
);
DROP TABLE IF EXISTS person_book;
CREATE TABLE person_book (
	id int unsigned auto_increment,
	person_id int unsigned NOT NULL default 0,
	book_id int unsigned NOT NULL default 0,
	PRIMARY KEY (id)
);
DROP TABLE IF EXISTS category;
CREATE TABLE category (
	id int unsigned auto_increment,
	category_id int unsigned NOT NULL default 0,
	title varchar(100) NOT NULL default '',
	PRIMARY KEY (id)
);