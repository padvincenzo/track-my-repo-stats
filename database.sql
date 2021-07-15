create table project (
  idproject int auto_increment not null primary key,
  name varchar(150) not null,
  slug varchar(150) not null
);

create table project_asset (
  idasset int auto_increment not null primary key,
  idproject int not null references project(idproject),
  tag varchar(15) not null,
  filename varchar(200) not null
);

create table project_downloads (
  log_date date not null default(CURRENT_DATE),
  idasset int not null references project_asset(idasset),
  dl_count int not null,
  primary key (idasset, log_date)
);
