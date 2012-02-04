-- Note: All data in this file should be UTF-8 encoded.  This means that any
-- characters above 127 must be properly encoded, NOT simply the iso-8859-1
-- representation.  Failure to do so will result in bogus display in the user's
-- browser, which expects UTF-8.
\connect - postgres
CREATE SEQUENCE "dc_user_id_seq" start 2 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
SELECT nextval ('"dc_user_id_seq"');
CREATE SEQUENCE "dc_user_public_info_id_seq" start 2 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
SELECT nextval ('"dc_user_public_info_id_seq"');
CREATE SEQUENCE "dc_articles_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_comments_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_topics_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_sections_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_chat_room_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_article_states_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_im_messages_id_seq" start 4 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
SELECT nextval ('"dc_im_messages_id_seq"');
CREATE SEQUENCE "dc_blog_recommendations_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_gpg_keygen_queue_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_forum_categories_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_forum_forums_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_im_notify_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_online_status_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_im_buddy_list_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_forum_topics_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE SEQUENCE "dc_categories_id_seq" start 1 increment 1 maxvalue 2147483647 minvalue 1  cache 1 ;
CREATE TABLE "dc_user" (
	"id" int4 DEFAULT nextval('dc_user_id_seq'::text) NOT NULL,
	"username" text NOT NULL,
	"password" character(32) NOT NULL,
	"access_level" int2 NOT NULL,
	"prefs" text,
	"username_hash" character(32),
	"user_image_src" text,
	PRIMARY KEY ("id")
);
CREATE TABLE "dc_topics" (
	"id" int4 DEFAULT nextval('dc_topics_id_seq'::text) NOT NULL,
	"name" text,
	"description" text,
	"category_id" int4,
	"display_order" int4
);
CREATE TABLE "dc_article_states" (
	"id" int4 DEFAULT nextval('dc_article_states_id_seq'::text) NOT NULL,
	"name" text,
	"description" text
);
CREATE TABLE "dc_blog_recommendations" (
	"id" int4 DEFAULT nextval('dc_blog_recommendations_id_seq'::text) NOT NULL,
	"user_id" int4,
	"user_id_blog" int4
);
CREATE TABLE "dc_im_buddy_list" (
	"id" int4 DEFAULT nextval('dc_im_buddy_list_id_seq'::text) NOT NULL,
	"user_id" int4 NOT NULL,
	"user_id_buddy" int4 NOT NULL,
	PRIMARY KEY ("id")
);
CREATE TABLE "dc_im_messages" (
	"id" int4 DEFAULT nextval('dc_im_messages_id_seq'::text) NOT NULL,
	"date" int4,
	"user_id_from" int4,
	"folder_id" int4,
	"body" text,
	"read_flag" bool,
	"user_id_owner" int4,
	"subject" text,
	"user_id_to" text
);
CREATE TABLE "dc_im_addresses" (
	"user_id" text NOT NULL,
	"addresses" text NOT NULL,
	PRIMARY KEY ("user_id")
);
CREATE TABLE "dc_categories" (
	"id" int4 DEFAULT nextval('dc_categories_id_seq'::text) NOT NULL,
	"name" text NOT NULL,
	"description" text,
	"display_order" int4,
	PRIMARY KEY ("id")
);
CREATE TABLE "dc_user_public_info" (
	"id" int4 DEFAULT nextval('dc_user_public_info_id_seq'::text) NOT NULL,
	"user_id" int4,
	"email" text,
	"www" text,
	"specialties" text,
	"company_association" text,
	"dmt_usd_claim_number" text,
	"quote" text,
	"comments" text,
	"user_since" text
);
CREATE TABLE "dc_comment_view_counts" (
	"comment_id" text NOT NULL,
	"count" int4 NOT NULL,
	PRIMARY KEY ("comment_id")
);
CREATE TABLE "dc_comments" (
	"date" int4,
	"parent_type" character(2),
	"user_id" int4 NOT NULL,
	"subject" text NOT NULL,
	"body" text NOT NULL,
	"state" int2,
	"icon" text,
	"parent_id" text NOT NULL,
   "topic_id" INTEGER,
	"id" text NOT NULL,
	"view_count" int4 DEFAULT 1,
	"cross_post_parent_id" text
);
CREATE TABLE "dc_articles" (
	"date" int4,
	"topic_id" int4,
	"user_id" int4,
	"subject" text,
	"leadin" text,
	"body" text,
	"state_id" int2,
	"id" text
);
CREATE TABLE "dc_gpg_keygen_queue" (
	"user_id" int4,
	"batch_data" text,
	"date" int4,
	"keyring_directory" text,
	"notification_recipient" text
);
COPY "dc_user" FROM stdin;
3293	Sysop	5f4dcc3b5aa765d61d8327deb882cf99	10		73850afb68a28c03ef4d2e426634e041	group_ruby/Dexter.gif
3294	Crypto_Engine	5f4dcc3b5aa765d61d8327deb882cf99	1	\N	d87b108a8f7e9f0b5ae35f9c61dae012	group_zeldman/rob.gif
1	Invisible_Man	5f4dcc3b5aa765d61d8327deb882cf99	10		6666b598c1b9b23152e1e0ecebfc30f2	group_ruby/neo1.gif
2	ellison	5f4dcc3b5aa765d61d8327deb882cf99	10		991519043f37b2ea6cfbbe05af45f470	group_ruby/Mac.gif
\.
COPY "dc_topics" FROM stdin;
136	General	General Distributed City discussion	21	5
149	Africa		31	5
150	Asia		31	10
151	Caribbean		31	15
153	Europe		31	25
152	Central America		31	20
154	Middle East		31	30
155	North Amerika		31	35
156	Oceania		31	40
157	Polar Regions		31	45
158	South America		31	50
140	General	General Cypherpunk discussion	22	5
137	Bug Reports	Malfunctions, misspellings, confusing instructions, and errors will get attention here.	21	10
138	Developers	Forum for general developers' discussions.	21	15
139	Feature Requests	Your ideas for needed or desirable DC features will get attention here.	21	20
141	General	General Culture discussion	23	5
142	General	General Classifieds	24	5
143	General	General discussion of Freedom Tools	25	5
144	General	General Health discussion	26	5
145	General	General Law discussion	27	5
146	General	General Money discussion	28	5
147	General	General Philosophy discussion	29	5
148	General	General Political discussion	30	5
159	General	General Sovereign Living discussion	32	5
160	General	General Technology discussion	33	5
161	General	General Travel discussion	34	5
162	Beginners	Those new to Distributed City will get answers to questions here.	21	7
\.
-- Added 9/26/02 by gente_libre
COPY "dc_topics" FROM stdin;
163	Humor	Jokes, funnies, looking on the lighter side of life	23	5
164	Invisible IRC Project	A project to create an anonymous and secure Internet Relay Chat network	25	10
165	General	General Ventures discussion	35	5
166	Brainstorming	New venture ideas for discussion, reality checking	35	10
167	Press releases	Press releases including new product announcements	35	15
168	General	General Free Nation Projects discussion	36	5
169	Limón REAL	A project to create a paradise of economic and individual freedom in Costa Rica, Central America	36	10
\.
COPY "dc_article_states" FROM stdin;
1	user	
2	submitted	
4	section	
5	frontpage	
3	passed over	
\.
COPY "dc_blog_recommendations" FROM stdin;
\.
COPY "dc_im_buddy_list" FROM stdin;
\.
COPY "dc_im_messages" FROM stdin;
\.
COPY "dc_im_addresses" FROM stdin;
\.
COPY "dc_categories" FROM stdin;
21	Distributed City	Developer discussion, feature requests, bug reports, beginner's forum	5
22	Cypherpunk	Cryptography, steganography, anonymizers, digital cash, crypto-anarchy	10
23	Culture	Media, movies, music, humor, art, the written word	15
24	Classifieds	Products and services available, wanted or offered, personals	20
25	Freedom Tools	Developing for and using OpenPGP, Mixmaster, IIP, DMT, E-Language, etc.	25
26	Health	Diet, exercise, longevity, smart drugs & nutrients, entheogens	25
27	Law	Jurisprudence, contracts, arbitration, reputation, intellectual property, economic protocols.	30
28	Money	Economics, private currencies, banking secrecy, offshore finance	35
29	Philosophy	Metaphysics, epistemology, ethics, politics, aesthetics	40
30 Anarcho-capitalism, libertarianism, Objectivism, the Nation-state circus	45
31	Regional	Virtual and meatspace places and jurisdictions.	50
32	Sovereign Living	Achieving liberty and prosperity outside the reach of the State.	55
33	Technology	Hardware, software, nanotech, biotech, AI	60
34	Travel	Perpetual Traveller, gatherings, lodging, stories, recommendations	65
\.
-- Added 9/26/02 by gente_libre
COPY "dc_categories" FROM stdin;
35	Ventures	 New venture press releases, brainstorming	70
36	Free Nation Projects	Projects to establish free nations, autonomous regions, region states.	23
\.
COPY "dc_user_public_info" FROM stdin;
\.
COPY "dc_comment_view_counts" FROM stdin;
\.
COPY "dc_comments" FROM stdin;
\.
COPY "dc_articles" FROM stdin;
\.
COPY "dc_gpg_keygen_queue" FROM stdin;
\.
CREATE UNIQUE INDEX "dc_user_username_key" on "dc_user" using btree ( "username" "text_ops" );
CREATE UNIQUE INDEX "dc_topics_id_key" on "dc_topics" using btree ( "id" "int4_ops" );
CREATE UNIQUE INDEX "username_hash_dc_user_ukey" on "dc_user" using btree ( "username_hash" "bpchar_ops" );
CREATE UNIQUE INDEX "dc_article_states_id_key" on "dc_article_states" using btree ( "id" "int4_ops" );
CREATE UNIQUE INDEX "dc_blog_recommendations_id_key" on "dc_blog_recommendations" using btree ( "id" "int4_ops" );
CREATE  INDEX "dc_im_buddy_list_id_key" on "dc_im_buddy_list" using btree ( "id" "int4_ops" );
CREATE  INDEX "dc_im_buddy_list_user_id_key" on "dc_im_buddy_list" using btree ( "user_id" "int4_ops" );
CREATE UNIQUE INDEX "dc_im_messages_id_key" on "dc_im_messages" using btree ( "id" "int4_ops" );
CREATE  INDEX "id_dc_im_messages_key" on "dc_im_messages" using btree ( "id" "int4_ops" );
CREATE  INDEX "user_id_to_dc_im_messages_key" on "dc_im_messages" using btree ( "user_id_to" "text_ops" );
CREATE  INDEX "dc_im_addresses_user_id_key" on "dc_im_addresses" using btree ( "user_id" "text_ops" );
CREATE UNIQUE INDEX "dc_categories_id_key1" on "dc_categories" using btree ( "id" "int4_ops", "name" "text_ops" );
CREATE  INDEX "dc_categories_id_key" on "dc_categories" using btree ( "id" "int4_ops" );
CREATE  INDEX "dc_categories_name_key" on "dc_categories" using btree ( "name" "text_ops" );
CREATE  INDEX "category_id_dc_topics_key" on "dc_topics" using btree ( "category_id" "int4_ops" );
CREATE UNIQUE INDEX "dc_user_public_info_id_key" on "dc_user_public_info" using btree ( "id" "int4_ops" );
CREATE  INDEX "user_id_dc_user_public_info_key" on "dc_user_public_info" using btree ( "user_id" "int4_ops" );
CREATE UNIQUE INDEX "user_id_dc_user_public_info_uke" on "dc_user_public_info" using btree ( "user_id" "int4_ops" );
CREATE  INDEX "dc_comment_counts_comment_id_ke" on "dc_comment_view_counts" using btree ( "comment_id" "text_ops" );
CREATE  INDEX "id_dc_comments_key" on "dc_comments" using btree ( "id" "text_ops" );
CREATE UNIQUE INDEX "id_dc_comments_ukey" on "dc_comments" using btree ( "id" "text_ops" );
CREATE  INDEX "parent_id_dc_comments_key" on "dc_comments" using btree ( "parent_id" "text_ops" );
CREATE  INDEX "cross_post_parent_id_dc_comment" on "dc_comments" using btree ( "cross_post_parent_id" "text_ops" );
CREATE  INDEX "id_dc_articles_key" on "dc_articles" using btree ( "id" "text_ops" );
CREATE UNIQUE INDEX "id_dc_articles_ukey" on "dc_articles" using btree ( "id" "text_ops" );
CREATE  INDEX "user_image_src_dc_user_key" on "dc_user" using btree ( "user_image_src" "text_ops" );
CREATE  INDEX "user_id_dc_gpg_keygen_queue_key" on "dc_gpg_keygen_queue" using btree ( "user_id" "int4_ops" );
CREATE UNIQUE INDEX "user_id_dc_gpg_keygen_queue_uke" on "dc_gpg_keygen_queue" using btree ( "user_id" "int4_ops" );
CREATE  INDEX "batch_data_dc_gpg_keygen_queue_" on "dc_gpg_keygen_queue" using btree ( "batch_data" "text_ops" );
CREATE  INDEX "date_dc_gpg_keygen_queue_key" on "dc_gpg_keygen_queue" using btree ( "date" "int4_ops" );
